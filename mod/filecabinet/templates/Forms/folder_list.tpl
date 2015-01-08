{ADMIN_LINKS}
<table class="table table-striped table-hover">
    <tr>
        <th style="width : 15%">&#160;</th>
        <th style="width : 30%">{TITLE_SORT}</th>
        <th style="width : 15%">{PUBLIC_FOLDER_SORT}</th>
        <th style="width : 8%">{ITEM_LABEL}</th>
        <!-- BEGIN modcreated -->
        <th>{MODULE_CREATED_SORT}</th>
        <!-- END modcreated -->
    </tr>
    <!-- BEGIN listrows -->
    <tr class="{TOGGLE}">
        <td class="admin-icons">
            {LINKS}
        </td>
        <td>{TITLE}</td>
        <td>{PUBLIC}</td>
        <td>{ITEMS}</td>
        <!-- BEGIN mod -->
        <td>{MODULE_CREATED}</td>
        <!-- END mod -->
    </tr>
    <!-- END listrows -->
</table>
{EMPTY_MESSAGE}
<div class="align-center">{TOTAL_ROWS}<br />
    {PAGE_LABEL} {PAGES}<br />
    {LIMIT_LABEL} {LIMITS}</div>
<div class="align-right">{FILE_SEARCH}</div>
