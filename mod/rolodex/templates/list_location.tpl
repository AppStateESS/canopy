{ALPHA_CLICK}
<!-- BEGIN add_link --><div align="right">{ADD_LINK}</div><!-- END add_link -->
<table width="99%" cellpadding="4">
    <tr>
        <th>{TITLE_HEADER} {TITLE_SORT}</th>
        <th>{DESCRIPTION_HEADER} {DESCRIPTION_SORT}</th>
        <th>&nbsp;</th>
    </tr>
<!-- BEGIN listrows -->
    <tr {TOGGLE}>
        <td nowrap="nowrap">{TITLE}</td>
        <td>{DESCRIPTION}</td>
        <td>{ACTION}</td>
    </tr>
<!-- END listrows -->
</table>
{EMPTY_MESSAGE}
<div class="align-center">
    {TOTAL_ROWS}<br />
    {PAGE_LABEL} {PAGES}<br />
    {LIMIT_LABEL} {LIMITS}
</div>
<div class="align-right">
    {SEARCH}
</div>
