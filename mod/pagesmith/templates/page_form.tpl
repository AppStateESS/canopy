{START_FORM}
<input type="hidden" name="title" id="page-title-hidden" value="{PAGE_TITLE}" />
{PAGE_TEMPLATE}
<hr />
<div class="align-center">{SUBMIT} {SAVE_SO_FAR}</div>
<hr />
{PUBLISH_DATE_LABEL} {PUBLISH_DATE}
<hr />
{TEMPLATE_LIST} {CHANGE_TPL} {ORPHAN_LINK}
{END_FORM}
<!-- BEGIN orphans -->
{ORPHANS}
<!-- END orphans -->
<div id="block-edit-popup" style="display:none"><textarea id="block-edit-textarea"></textarea></div>
<div id="title-edit-popup" style="display:none"><input type="text" id="page-title-input" name="page_title" class="form-control" value="{PAGE_TITLE}" /></div>