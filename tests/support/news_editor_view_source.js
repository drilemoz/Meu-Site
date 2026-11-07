'use strict';
const fs=require('node:fs');
const path=require('node:path');
// Fonte real de interface em ordem fixa, usada pelos testes estruturais legados.
module.exports=function newsEditorViewSource(){
  const dir=path.join(__dirname,'../../app/news_admin_view');
  return ['header.php','details.php','blocks.php','media.php','footer.php','revisions.php','scripts.php']
    .map(name=>fs.readFileSync(path.join(dir,name),'utf8')).join('');
};
