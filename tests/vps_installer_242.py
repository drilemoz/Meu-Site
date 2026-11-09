#!/usr/bin/env python3
"""Ensaios offline: sem root, sem nginx real e sem mexer em dados persistentes."""
from __future__ import annotations
from importlib.util import module_from_spec, spec_from_file_location
from pathlib import Path
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[1]
HELPER = ROOT / "deploy/aws/nginx_upstream.py"
INSTALL = ROOT / "deploy/aws/install.sh"
spec = spec_from_file_location("ngua_nginx", HELPER)
mod = module_from_spec(spec)
assert spec and spec.loader
spec.loader.exec_module(mod)
SITE = "/var/www/projetos/iguatamaonline/public"
BASIC = f"server {{\n root {SITE};\n location ~ \\.php$ {{\n fastcgi_pass unix:/run/php/php8.3-fpm.sock;\n }}\n}}\n"

class VPSInstaller(unittest.TestCase):
    def test_accepts_dedicated_nginx(self):
        self.assertEqual(mod.inspect(BASIC, SITE, "8.3"), "unix:/run/php/php8.3-fpm.sock")
        updated = mod.transform(BASIC, SITE, "8.3")
        self.assertIn("fastcgi_pass unix:/run/php/php8.3-fpm-iguatamaonline.sock;", updated)
        self.assertEqual(updated, mod.transform(updated, SITE, "8.3"), "reexecução idempotente")

    def test_rejects_shared_or_ambiguous_config(self):
        variants = [
            BASIC.replace("server {", "server {\n root /var/www/iguatama-teste/public;"),
            BASIC + "\nserver { root /outro-site/public; fastcgi_pass 127.0.0.1:9000; }",
            BASIC.replace("fastcgi_pass unix:/run/php/php8.3-fpm.sock;", "fastcgi_pass 127.0.0.1:9000;"),
            BASIC.replace("fastcgi_pass", "# fastcgi_pass"),
            BASIC.replace(SITE, "/var/www/iguatama-teste/public"),
            BASIC.replace("fastcgi_pass unix:/run/php/php8.3-fpm.sock;", "fastcgi_pass unix:/run/php/php8.3-fpm.sock;\n fastcgi_pass unix:/run/php/php8.3-fpm.sock;"),
        ]
        for v in variants:
            with self.subTest(config=v[:80]), self.assertRaises(ValueError):
                mod.transform(v, SITE, "8.3")

    def test_comments_are_preserved(self):
        src = "# root /another/site;\n# fastcgi_pass 127.0.0.1:9000;\n" + BASIC
        out = mod.transform(src, SITE, "8.3")
        self.assertIn("# fastcgi_pass 127.0.0.1:9000;", out)
        self.assertEqual(out.count("fpm-iguatamaonline.sock;"), 1)

    def test_cli_check_and_apply_fixture(self):
        with tempfile.TemporaryDirectory() as folder:
            conf = Path(folder)/"site.conf"
            conf.write_text(BASIC)
            args = ["python3", str(HELPER), "--config", str(conf), "--site-public", SITE]
            subprocess.run(args, check=True, capture_output=True, text=True)
            self.assertEqual(conf.read_text(), BASIC, "--check não altera")
            subprocess.run(args + ["--apply"], check=True, capture_output=True, text=True)
            changed = conf.read_text()
            subprocess.run(args + ["--apply"], check=True, capture_output=True, text=True)
            self.assertEqual(conf.read_text(), changed, "--apply idempotente")

    def test_bash_syntax_help_and_preflight(self):
        subprocess.run(["bash", "-n", str(INSTALL)], check=True)
        p = subprocess.run(["bash", str(INSTALL), "--help"], text=True, capture_output=True)
        self.assertEqual(p.returncode, 0)
        self.assertIn("--check", p.stdout)
        source = INSTALL.read_text()
        for clause in (
            '[[ "$EUID" -eq 0 ]]', 'IGUA_DEPLOY_VPS',
            '"$SITE/public/uploads" -prune', "aws-deploy-check.php", "install -d",
            'systemctl enable --now iguatama-deploy.timer', 'systemctl stop iguatama-deploy.timer',
            'CONFIG_CHANGED=1', "--ack-backup", 'test -w "$path"',
            "iguatama-deploy.service", "runuser -u", "--apply",
        ):
            self.assertIn(clause, source, clause)
        self.assertNotIn("chmod 777", source.replace('nunca usar chmod 777', ''))
        self.assertNotIn("rm -rf", source)

if __name__ == "__main__":
    unittest.main(verbosity=2)
