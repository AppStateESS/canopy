<script type="text/javascript" src="{source_http}javascript/editors/fckeditor/fckeditor.js"></script>
<script type="text/javascript">
//<![CDATA[
var base_http = '{source_http}';
var home_http = '{home_http}';
/* This code only works on a source code submission. Hoping for a
//change in the editor
*/

$(document).ready(function() {
    $('form').submit(function() {
        $('textarea').map(function(){
            ta_name = $(this).attr('name');
            oEditor = FCKeditorAPI.GetInstance(ta_name);
            html_content = oEditor.GetData();
            new_html = html_content.replace(/="..\/..\/..\/..\//, '="./');
            $(this).text(new_html);
            oEditor.SetData(new_html);
        });
    });
});

//]]>
</script>
