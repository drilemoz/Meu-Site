<?php
/**
 * Monta o texto-fonte de uma view separada em parciais, sem executar PHP.
 * Usado apenas pelos testes estáticos do contrato das integrações.
 */
function igua_test_integrations_source(): string
{
    $root=dirname(__DIR__,2);
    $file=$root.'/app/admin_views/routes/integrations.php';
    $source=(string)file_get_contents($file);
    return preg_replace_callback(
        '~<\?php require __DIR__\.\'/integrations/([a-z0-9_]+)\.php\'; \?>\n~',
        static function(array $match) use ($root): string {
            $file=$root.'/app/admin_views/routes/integrations/'.$match[1].'.php';
            if (!is_file($file)) {
                throw new RuntimeException('Componente de integrações ausente: '.$match[1]);
            }
            return (string)file_get_contents($file);
        },
        $source
    );
}
