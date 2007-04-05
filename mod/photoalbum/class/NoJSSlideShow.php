<?php
  /**
   * This class is responsible for displaying a slide show for photoalbum using no javascript.
   *
   * @version $Id: NoJSSlideShow.php 9 2006-06-05 20:24:53Z matt $
   * @author  Darren Greene <dg49379@NOSPAM.tux.appstate.edu>
   * @modified Matthew McNaney <mcnaney at gmail dot com>
   */
class NoJSSlideShow {
    var $index;
    var $max_images;
    var $widths = array();
    var $heights = array();
    var $names = array();
    var $filenames = array();
    var $descriptions = array();
    var $largest_height;
    var $album_id;
    var $mode;

    function NoJSSlideShow($photos, $albumID) {
        $this->mode = 'normal';
        $this->index = 0;
        $this->max_images = count($photos);
        $this->album_id = $albumID;

        for($i = 0; $i < count($photos); $i++) {
            $photo = new PHPWS_Photo($photos[$i]);

            $filenameStr  = './images/photoalbum/';
            $filenameStr .= $photo->_album . '/';
            $filenameStr .= $photo->_name;
            $this->filenames[$i] = $filenameStr;

            $this->names[$i] = $photo->getLabel();
            $this->descriptions[$i] = $photo->_blurb;

            if ((($photo->_width >= PHOTOALBUM_MAX_WIDTH ||
                  $photo->_height >= PHOTOALBUM_MAX_HEIGHT))) {
                $ratio = $photo->_width / $photo->_height;

                if ($ratio >= 1) {
                    $this->widths[$i] = PHOTOALBUM_MAX_WIDTH;
                    $this->heights[$i] = PHOTOALBUM_MAX_WIDTH / $ratio;

                    if((PHOTOALBUM_MAX_HEIGHT / $ratio) > $this->largest_height)
                        $this->largest_height = PHOTOALBUM_MAX_HEIGHT / $ratio;
                } else {
                    $this->widths[$i] = PHOTOALBUM_MAX_HEIGHT * $ratio;
                    $this->heights[$i] = PHOTOALBUM_MAX_HEIGHT;

                    if(PHOTOALBUM_MAX_HEIGHT > $this->largest_height)
                        $this->largest_height = PHOTOALBUM_MAX_HEIGHT;
                }
            } else {
                $this->widths[$i] = $photo->_width;
                $this->heights[$i] = $photo->_height;

                if($photo->_height > $this->largest_height)
                    $this->largest_height = $photo->_height;
            }
        }
    }

    function getCount() {
        return $this->max_images;
    }

    function indexNotAtStart() {
        if($this->index != 0)
            return true;
        else
            return false;
    }

    function indexNotAtEnd() {
        if($this->index < $this->max_images - 1 )
            return true;
        else
            return false;
    }

    function advanceIndex() {
        if($this->index < $this->max_images - 1 ) {
            $this->index++;
        }
    }

    function decrementIndex() {
        if($this->index != 0)
            $this->index--;
    }

    function play() {
        if(isset($_REQUEST['SS_mode']) && $_REQUEST['SS_mode'] == 'nojsmode')
            $this->mode = 'switched';
        else
            $this->mode = 'normal';

        if(isset($_REQUEST['SS_op']) && $_REQUEST['SS_op'] == 'adv')
            $this->advanceIndex();
        else if(isset($_REQUEST['SS_op']) && $_REQUEST['SS_op'] == 'prv')
            $this->decrementIndex();
        else
            $this->index = 0;

        $tags['LARGEST_IMHEIGHT'] = $this->largest_height + 80;
        $tags['QUIT_SLIDESHOW'] =
            "<a href='./index.php?module=photoalbum&amp;" .
            "PHPWS_Album_op=view&amp;PHPWS_Album_id=".
            $_SESSION['PHPWS_AlbumManager']->album->_id . "'>" .
            _("Back&nbsp;to&nbsp;Album") . "</a>";

        if($this->indexNotAtStart()) {
            $address = './index.php';
            $linkImage = "<img border='0' valign='bottom' " .
                "src='http://" . PHPWS_SOURCE_HTTP . "mod/photoalbum/img/previous_arrow.jpg' /> ";

            $get_var['PHPWS_Album_op'] = 'slideShow';
            $get_var['SS_op'] = 'prv';

            if($this->mode == 'switched')
                $get_var['SS_mode'] = 'nojsmode';

            $linkText = dgettext('photoalbum', 'Previous');

            $tags['PREVIOUS'] =
                PHPWS_Text::moduleLink($linkImage, 'photoalbum', $get_var) . '&nbsp;' .
                PHPWS_Text::moduleLink($linkText, 'photoalbum', $get_var);
        }

        if($this->indexNotAtEnd()) {
            $address = './index.php';
            $linkText = dgettext('photoalbum', 'Next');
            $linkImage = " <img border='0' src='images/mod/photoalbum/forward_arrow.jpg' />";

            $get_var['PHPWS_Album_op'] = 'slideShow';
            $get_var['SS_op'] = 'adv';

            if($this->mode == 'switched')
                $get_var['SS_mode'] = 'nojsmode';
      
            $tags['NEXT'] =
                PHPWS_Text::moduleLink($linkText, 'photoalbum', $get_var) . '&nbsp;' .
                PHPWS_Text::moduleLink($linkImage, 'photoalbum', $get_var);
        }

        $tags['IMAGE_SRC'] = $this->filenames[$this->index];
        $tags['IMAGE_NAME'] = $this->names[$this->index];
        $tags['IMAGE_BLURB'] = $this->descriptions[$this->index];
        $tags['IMAGE_INDEX_INFO'] = dgettext('photoalbum', 'Image ') .
            ($this->index + 1) . ' ' . dgettext('photoalbum', 'of') . ' '.$this->max_images;
        $tags['IMAGE_WIDTH'] = $this->widths[$this->index];
        $tags['IMAGE_HEIGHT'] = $this->heights[$this->index];

        if($this->mode == 'switched') {
            $tags['HIGH_TECH_LINK'] = dgettext('photoalbum', 'Return to ');

            $address = './index.php';
            $linkText = dgettext('photoalbum', 'high tech');
            $get_var['PHPWS_Album_op'] = 'slideShow';

            $tags['HIGH_TECH_LINK'] .= PHPWS_Text::moduleLink($linkText, 'photoalbum', $get_var);

            $tags['HIGH_TECH_LINK'] .= dgettext('photoalbum', ' mode.');
        }

        return PHPWS_Template::processTemplate($tags, 'photoalbum',
                                               'slideshow/noJSslideshow.tpl');
    }
}

?>