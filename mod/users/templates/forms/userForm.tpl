<!-- BEGIN message -->
<span class="smalltext">{MESSAGE}</span>
<hr />
<!-- END message -->
<div class="align-right">{LINKS}</div>
{START_FORM}
<table class="form-table" cellspacing="0" cellpadding="4">
  <tr>
    <td>{AUTHORIZE_LABEL}</td><td>{AUTHORIZE}</td>
  </tr>
  <tr>
    <td>{USERNAME_LABEL}</td><td>{USERNAME}</td>
  </tr>
  <!-- BEGIN username-error -->
  <tr><td class="user-error" colspan="2">{USERNAME_ERROR}</td></tr>
  <!-- END username-error -->
  <tr>
    <td>{DISPLAY_NAME_LABEL}</td><td>{DISPLAY_NAME}</td>
  </tr>
  <tr>
    <td>{PASSWORD1_LABEL}</td><td>{PASSWORD1}&nbsp;{PASSWORD2}</td>
  </tr>
  <!-- BEGIN password-error -->
  <tr><td class="user-error" colspan="2">{PASSWORD_ERROR}</td></tr>
  <!-- END password-error -->
  <tr>
    <td>{EMAIL_LABEL}</td><td>{EMAIL}</td>
  </tr>
  <!-- BEGIN email-error -->
  <tr><td class="user-error" colspan="2">{EMAIL_ERROR}</td></tr>
  <!-- END email-error -->
</table>
{SUBMIT}
{END_FORM}
