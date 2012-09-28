// get path of directory ckeditor
var basePath = CKEDITOR.basePath;
basePath = basePath.substr(0, basePath.indexOf("ckeditor/"));

//load external plugin
(function() {
   CKEDITOR.plugins.addExternal('filecabinet',basePath+'ckeditor/phpws_plugins/filecabinet/', 'plugin.js');
})();


CKEDITOR.editorConfig = function( config )
{
    config.toolbar = 'MyToolbar';
    config.extraPlugins = 'filecabinet';
    config.height = 300;

    config.toolbar_MyToolbar =
    [
        ['Source','-','Templates'],
        ['Cut','Copy','Paste','PasteText','PasteFromWord','-', 'SpellChecker', 'Scayt'],
        ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
        '/',
        ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
        ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
        ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
        ['Link','Unlink','Anchor'],
        ['Image', 'Filecabinet', 'Table','HorizontalRule','SpecialChar'],
        '/',
        ['Styles','Format','Font','FontSize'],
        ['TextColor','BGColor'],
        ['Maximize', 'ShowBlocks','-','About']
    ];
};
