<div id="user-login">
    <!-- BEGIN login-form -->
    {START_FORM}
    {PHPWS_USERNAME_LABEL}<br />{PHPWS_USERNAME}<br />
    {PHPWS_PASSWORD_LABEL}<br />{PHPWS_PASSWORD}<br />
    {SUBMIT}<br />
    {END_FORM}
    <!-- BEGIN links -->
    <hr />
    <div class="align-center">
        <!-- BEGIN signup -->
        <span class="smalltext">{NEW_ACCOUNT}</span>
        <br />
        <!-- END signup -->
        <!-- BEGIN forgot -->
        <span class="smalltext">{FORGOT}</span>
        <!-- END forgot -->
    </div>
<!-- END links -->
<!-- END login-form -->
<!-- BEGIN logged-in -->
<div class="box">
    <div class="box-title"><h1>{DISPLAY_NAME}</h1></div>
    <div class="box-content">
        {HOME}<br />
        {MODULES}<br />
        <hr />
        {LOGOUT}
    </div>
</div>
<!-- END logged-in -->
</div>
