<div class="comments">
    <div class="page-select bgcolor1">
        {START_FORM}<!-- BEGIN page-select --><strong>{PAGE_LABEL}:</strong> {PAGES}&nbsp;|&nbsp;
        <!-- END page-select -->{NEW_POST_LINK}
        {TIME_PERIOD}{ORDER}{SUBMIT}{END_FORM}
    </div>
    <div class="padded">{EMPTY_MESSAGE}</div>
    <!-- BEGIN listrows -->
    <table class="comment-table">
        <tr>
            <td colspan="2" class="bgcolor3 smaller padded" >{RELATIVE_CREATE}
                <!-- BEGIN response --> - {RESPONSE_LABEL} {RESPONSE_NAME}<!-- END response -->
        <tr>
            <td class="author-info bgcolor1" valign="top">
               <h2>{AUTHOR_NAME} <!-- BEGIN ip -->- {IP_ADDRESS}<!-- END ip --></h2>
               {AVATAR}
            </td>
            <td class="comment-body">
                <h2>{SUBJECT}</h2>
                <div class="entry" style="border-top : 1px gray dotted">{ENTRY}</div>
                <!-- BEGIN signature --><div class="signature">{SIGNATURE}</div><!-- END signature -->
                <!-- BEGIN edit-info --><p class="edit-info">{EDIT_LABEL}: {EDIT_AUTHOR} ({EDIT_TIME})
                <!-- BEGIN reason --><br />{EDIT_REASON_LABEL}: {EDIT_REASON}<!-- END reason --></p>
                <!-- END edit-info -->
                <div class="admin-links"><!-- BEGIN edit-link -->{EDIT_LINK}
                    <!-- BEGIN delete-link -->| {DELETE_LINK}<!-- END delete-link -->
                    | <!-- END edit-link --> {REPLY_LINK} | {QUOTE_LINK}
                </div>
            </td>
         </tr>
     </table>
    <!-- END listrows -->
</div>
<!-- BEGIN page-select2 -->
<div class="align-center">
    <strong>{PAGE_LABEL}:</strong>
    {PAGES}<br />
    {LIMITS}
</div>
<!-- END page-select2 -->
