<?php

  /**
   * @author Matthew McNaney <mcnaney at gmail dot com>
   * @version $Id$
   */

PHPWS_Core::initCoreClass('Form.php');

class Blog_Form {

    /**
     * @param boolean limited   If true, use anonymous submission form
     */
    function edit(&$blog, $version_id=NULL, $limited=false)
    {
        $form = new PHPWS_Form;
        $form->addHidden('module', 'blog');

        if ($limited) {
            $form->addHidden('action', 'post_suggestion');
            $form->addSubmit('submit', dgettext('blog', 'Suggest entry'));
        } else {
            $form->addHidden('action', 'admin');
            $form->addHidden('command', 'post_entry');

            if (isset($version_id)) {
                $form->addHidden('version_id', $version_id);
                if (Current_User::isUnrestricted('blog')) {
                    $form->addSubmit('approve_entry', dgettext('blog', 'Save Changes and Approve'));
                }
            }

            if (isset($blog->id) || isset($version_id)){
                $form->addHidden('blog_id', $blog->id);
                $form->addSubmit('submit', dgettext('blog', 'Update entry'));
            } else {
                $form->addSubmit('submit', dgettext('blog', 'Add entry'));
            }

            $form->addCheck('allow_comments', 1);
            $form->setLabel('allow_comments', dgettext('blog', 'Allow comments'));
            $form->setMatch('allow_comments', $blog->allow_comments);
            
            $form->addCheck('allow_anon', 1);
            $form->setLabeL('allow_anon', dgettext('blog', 'Allow anonymous comments'));
            if ($blog->id) {
                PHPWS_Core::initModClass('comments', 'Comments.php');
                $thread = Comments::getThread($blog->key_id);
                $form->setMatch('allow_anon', $thread->allow_anon);
            }

            if (PHPWS_Settings::get('blog', 'simple_image')) {
                PHPWS_Core::initModClass('filecabinet', 'Cabinet.php');
                $manager = Cabinet::imageManager($blog->image_id, 'image_id',
                                                 PHPWS_Settings::get('blog', 'max_width'),
                                                 PHPWS_Settings::get('blog', 'max_height'));
                if ($manager) {
                    $form->addTplTag('IMAGE_MANAGER', $manager->get());
                    $form->addTplTag('IMAGE_LABEL', dgettext('blog', 'Image'));
                }
            }
        }

        $form->addText('title', $blog->title);
        $form->setSize('title', 40);
        $form->setLabel('title', dgettext('blog', 'Title'));

        $form->addTextArea('summary', $blog->getSummary());
        if (!$limited) {
            $form->useEditor('summary');
        }
        $form->setRows('summary', '10');
        $form->setCols('summary', '60');
        $form->setLabel('summary', dgettext('blog', 'Summary'));

        $form->addTextArea('entry', $blog->getEntry());
        if (!$limited) {
            $form->useEditor('entry');
        }
        $form->setRows('entry', '10');
        $form->setCols('entry', '60');
        $form->setLabel('entry', dgettext('blog', 'Entry'));

        $form->addText('publish_date', $blog->getPublishDate());
        $form->setLabel('publish_date', dgettext('blog', 'Publish date/time'));
        $form->setSize('publish_date', 20);

        $form->addText('expire_date', $blog->getExpireDate());
        $form->setLabel('expire_date', dgettext('blog', 'Expire date/time'));
        $form->setSize('expire_date', 20);

        $template = $form->getTemplate();

        $template['EXAMPLE'] = 'YYMMDD HH:MM';
        return PHPWS_Template::process($template, 'blog', 'edit.tpl');
    }

    function settings()
    {
        $form = new PHPWS_Form;
        $form->addHidden('module', 'blog');
        $form->addHidden('action', 'admin');
        $form->addHidden('command', 'post_settings');

        $form->addText('blog_limit', PHPWS_Settings::get('blog', 'blog_limit'));
        $form->setSize('blog_limit', 2, 2);
        $form->setLabel('blog_limit', dgettext('blog', 'Blog view limit'));

        $form->addText('past_entries', PHPWS_Settings::get('blog', 'past_entries'));
        $form->setLabel('past_entries', dgettext('blog', 'Number of past entries'));
        $form->setSize('past_entries', 2, 2);

        $form->addCheck('allow_comments', 1);
        $form->setLabel('allow_comments', dgettext('blog', 'Allow comments by default'));
        $form->setMatch('allow_comments', PHPWS_Settings::get('blog', 'allow_comments'));

        $form->addCheck('simple_image', 1);
        $form->setLabel('simple_image', dgettext('blog', 'Use Image Manager'));
        $form->setMatch('simple_image', PHPWS_Settings::get('blog', 'simple_image'));

        $form->addCheck('home_page_display', 1);
        $form->setLabel('home_page_display', dgettext('blog', 'Show blog on home page'));
        $form->setMatch('home_page_display', PHPWS_Settings::get('blog', 'home_page_display'));

        $cache_view = PHPWS_Settings::get('blog', 'cache_view');
        $form->addCheck('cache_view', 1);
        $form->setLabel('cache_view', dgettext('blog', 'Cache anonymous view'));
        $form->setMatch('cache_view', $cache_view);
        if (!ALLOW_CACHE_LITE) {
            $form->setDisabled('cache_view');
            $form->addTplTag('RESET_CACHE', dgettext('blog', 'System caching disabled.'));
        } else {
            if ($cache_view) {
                $form->addTplTag('RESET_CACHE', PHPWS_Text::secureLink(dgettext('blog', 'Reset cache'), 'blog', array('action'=>'admin', 'command'=>'reset_cache')));
            }
        }

        $form->addCheck('allow_anonymous_submit', 1);
        $form->setLabel('allow_anonymous_submit', dgettext('blog', 'Allow anonymous submissions'));
        $form->setMatch('allow_anonymous_submit', PHPWS_Settings::get('blog', 'allow_anonymous_submit'));

        $form->addTextField('max_width', PHPWS_Settings::get('blog', 'max_width'));
        $form->setLabel('max_width', dgettext('blog', 'Maximum image width (50-2048)'));
        $form->setSize('max_width', 4,4);

        $form->addTextField('max_height', PHPWS_Settings::get('blog', 'max_height'));
        $form->setLabel('max_height', dgettext('blog', 'Maximum image height (50-2048)'));
        $form->setSize('max_height', 4,4);
        

        $form->addSubmit(dgettext('blog', 'Save settings'));

        $template = $form->getTemplate();

        if (PHPWS_Settings::get('blog', 'allow_anonymous_submit')) {
            $template['MENU_LINK'] = PHPWS_Text::secureLink(dgettext('blog', 'Clip for menu'), 'blog',
                                                            array('action'=>'admin', 'command'=>'menu_submit_link'));
        }

        $template['PAST_NOTE'] = dgettext('blog', 'Set to zero to prevent display');
        return PHPWS_Template::process($template, 'blog', 'settings.tpl');
    }
}
?>
