{START_FORM}
<table cellpadding="5" width="99%">
    <tr>
        <th width="1%">{CHECK_ALL}</th>
        <th>{SUBJECT_SORT} {SUBJECT_LABEL}</th>
        <th>{ENTRY_LABEL}</th>
        <th>{REPORTED_SORT} {REPORTED_LABEL}</th>
        <th>&nbsp;</th>
    </tr>
    <!-- BEGIN listrows -->
    <tr class="highlight">
        <td>{CHECK}</td>
        <td>{SUBJECT}</td>
        <td>{ENTRY}{FULL}</td>
        <td>{REPORTED}</td>
        <td>{ACTION}</td>
    </tr>
    <!-- END listrows -->
</table>
{AOP} {GO} {END_FORM}
<!-- BEGIN message -->
<p>{EMPTY_MESSAGE}</p>
<!-- END message -->
<div align="center"><b>{PAGE_LABEL}</b><br />
{PAGES}<br />
{LIMITS}</div>
