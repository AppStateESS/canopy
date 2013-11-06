<h1>phpWebSite {PHPWS_VERSION}</h1>
<!-- BEGIN directories -->
<div
    style="height: 80px; overflow: auto; border: 1px solid black; padding: 3px"
    ><pre>{DIRECTORIES}</pre></div>
<!-- END directories -->
<!-- BEGIN warning -->
<span class="error"><strong>{WARNING}</strong></span>
<br />
<br />
<!-- END warning -->
<table class="table table-striped table-hover">
    <tr>
        <th>{TITLE_LABEL}</th>
        <th>{VERSION_LABEL}</th>
        <th>{LATEST_LABEL}</th>
        <th>{COMMAND_LABEL}</th>
        <th>{ABOUT_LABEL}</th>
    </tr>
    <!-- BEGIN mod-row -->
    <tr class="bgcolor{ROW}">
        <td>{TITLE}</td>
        <td>{VERSION}</td>
        <td>{LATEST}</td>
        <td>{COMMAND} <!-- BEGIN uninstall -->&nbsp;|&nbsp;&nbsp;{UNINSTALL}<!-- END uninstall -->
        </td>
        <td>{ABOUT}</td>
    </tr>
    <!-- END mod-row -->
</table>
<div class="align-center">{CHECK_FOR_UPDATES}</div>
<!-- BEGIN old-mods -->
<div style="border: 4px double black; padding: 5px">{OLD_MODS}</div>
<!-- END old-mods -->
