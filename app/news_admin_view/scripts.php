<script type="application/json" id="news-blocks-initial"><?= json_encode($existing,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR) ?></script>
<script type="application/json" id="news-draft-initial"><?= json_encode($savedDraft?json_decode($savedDraft['payload'],true):null,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
<script src="/assets/js/news-editor.js" defer></script>
<script src="/assets/js/seo-audit.js" defer></script>
<script src="/assets/js/news-upload.js" defer></script>

<script src="/assets/js/news-gemini.js" defer></script>
