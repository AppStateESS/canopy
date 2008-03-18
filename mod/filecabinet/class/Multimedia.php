<?php

/**
 * @version $Id$
 * @author Matthew McNaney <mcnaney at gmail dot com>
 */

PHPWS_Core::requireConfig('filecabinet');
PHPWS_Core::initModClass('filecabinet', 'File_Common.php');

define('GENERIC_VIDEO_ICON', 'images/mod/filecabinet/video_generic.jpg');
define('GENERIC_AUDIO_ICON', 'images/mod/filecabinet/audio.png');

class PHPWS_Multimedia extends File_Common {
    var $width     = 0;
    var $height    = 0;
    var $thumbnail = null;
    /**
     * In seconds
     */
    var $duration  = 0;
    var $embedded  = 0;

    var $_classtype       = 'multimedia';

    function PHPWS_Multimedia($id=0)
    {
        $this->loadAllowedTypes();
        $this->setMaxSize(PHPWS_Settings::get('filecabinet', 'max_multimedia_size'));

        if (empty($id)) {
            return;
        }

        $this->id = (int)$id;
        $result = $this->init();
        if (PEAR::isError($result)) {
            $this->id = 0;
            $this->_errors[] = $result;
        } elseif (empty($result)) {
            $this->id = 0;
            $this->_errors[] = PHPWS_Error::get(FC_MULTIMEDIA_NOT_FOUND, 'filecabinet', 'PHPWS_Multimedia');
        }
        $this->loadExtension();
    }

    function init()
    {
        if (empty($this->id)) {
            return false;
        }

        $db = new PHPWS_DB('multimedia');
        return $db->loadObject($this);
    }


    function loadAllowedTypes()
    {
        $this->_allowed_types = explode(',', PHPWS_Settings::get('filecabinet', 'media_files'));
    }

    function getID3()
    {
        require_once PHPWS_SOURCE_DIR . 'lib/getid3/getid3/getid3.php';
        $getID3 = new getID3;

        // File to get info from
        $file_location = $this->getPath();
        // Get information from the file
        $fileinfo = $getID3->analyze($file_location);
        getid3_lib::CopyTagsToComments($fileinfo);
        return $fileinfo;
    }

    function loadDimensions()
    {
        $fileinfo = $this->getID3();

        if (isset($fileinfo['video']['resolution_x'])) {
            $this->width = & $fileinfo['video']['resolution_x'];
            $this->height = & $fileinfo['video']['resolution_y'];
        } elseif (isset($fileinfo['video']['streams'][2]['resolution_x'])) {
            $this->width = & $fileinfo['video']['streams'][2]['resolution_x'];
            $this->height = & $fileinfo['video']['streams'][2]['resolution_y'];
        } else {
            $this->width = PHPWS_Settings::get('filecabinet', 'default_mm_width');
            $this->height = PHPWS_Settings::get('filecabinet', 'default_mm_height');
        }

        $this->duration = (int)$fileinfo['playtime_seconds'];
    }


    function allowMultimediaType($type)
    {
        $mm = new PHPWS_Multimedia;
        return $mm->allowType($type);
    }

    function thumbnailDirectory()
    {
        return $this->file_directory . 'tn/';
    }

    function thumbnailPath()
    {
        if (!$this->thumbnail) {
            return null;
        }
        return $this->thumbnailDirectory() . $this->thumbnail;
    }


    function rowTags()
    {
        if (Current_User::allow('filecabinet', 'edit_folders', $this->folder_id, 'folder')) {
            $clip = sprintf('<img src="images/mod/filecabinet/clip.png" title="%s" />', dgettext('filecabinet', 'Clip media'));
            $links[] = PHPWS_Text::secureLink($clip, 'filecabinet',
                                              array('mop'=>'clip_multimedia',
                                                    'multimedia_id' => $this->id));
            $links[] = $this->editLink(true);
            $links[] = $this->deleteLink(true);
        }

        if (isset($links)) {
            $tpl['ACTION'] = implode('', $links);
        }
        $tpl['SIZE'] = $this->getSize(TRUE);
        $tpl['FILE_NAME'] = $this->file_name;
        $tpl['THUMBNAIL'] = $this->getJSView(true);
        $tpl['TITLE']     = $this->getJSView(false, $this->title);

        if ($this->isVideo()) {
            $tpl['DIMENSIONS'] = sprintf('%s x %s', $this->width, $this->height);
        }

        return $tpl;
    }

    function popupAddress()
    {
        if (MOD_REWRITE_ENABLED) {
            return sprintf('filecabinet/%s/multimedia', $this->id);
        } else {
            return sprintf('index.php?module=filecabinet&amp;mtype=multimedia&amp;id=%s', $this->id);
        }

    }


    function popupSize()
    {
        static $sizes = null;

        if (!$this->width) {
            $this->width = 100;
        }

        if (!$this->height) {
            $this->height = 100;
        }

        $dimensions = array(FC_MAX_MULTIMEDIA_POPUP_WIDTH, FC_MAX_MULTIMEDIA_POPUP_HEIGHT);
        if (isset($sizes[$this->id])) {
            return $sizes[$this->id];
        }
        $padded_width = $this->width + 40;
        $padded_height = $this->height + 120;

        if (!empty($this->description)) {
            $padded_height += round( (strlen(strip_tags($this->description)) / ($this->width / 12)) * 12);
        }

        if ( $padded_width < FC_MAX_MULTIMEDIA_POPUP_WIDTH && $padded_height < FC_MAX_MULTIMEDIA_POPUP_HEIGHT ) {
            $final_width = $final_height = 0;
            
            for ($lmt = 250; $lmt += 50; $lmt < 1300) {
                if (!$final_width && ($padded_width + 25) < $lmt) {
                    $final_width = $lmt;
                }
                
                if (!$final_height && ($padded_height + 25) < $lmt ) {
                    $final_height = $lmt;
                }
                
                if ($final_width && $final_height) {
                    $dimensions = array($final_width, $final_height);
                    break;
                }
            }
        }
        $sizes[$this->id] = $dimensions;
        return $dimensions;
    }

    function getJSView($thumbnail=false, $link_override=null)
    {
        if ($link_override) {
            $values['label'] = $link_override;
        } else {
            if ($thumbnail) {
                $values['label'] = $this->getThumbnail();
            } else {
                $values['label'] = sprintf('<img src="images/mod/filecabinet/viewmag+.png" title="%s" />',
                                           dgettext('filecabinet', 'View full image'));
            }
        }

        $size = $this->popupSize();
        $values['address']     = $this->popupAddress();
        $values['width']       = $size[0];
        $values['height']      = $size[1];
        $values['window_name'] = 'multimedia_view';
        return Layout::getJavascript('open_window', $values);
    }


    function editLink($icon=false)
    {
        $vars['mop'] = 'upload_multimedia_form';
        $vars['multimedia_id'] = $this->id;
        $vars['folder_id'] = $this->folder_id;
        
        $jsvars['width'] = 550;
        $jsvars['height'] = 620;
        $jsvars['address'] = PHPWS_Text::linkAddress('filecabinet', $vars, true);
        $jsvars['window_name'] = 'edit_link';
        
        if ($icon) {
            $jsvars['label'] =sprintf('<img src="images/mod/filecabinet/edit.png" title="%s" />', dgettext('filecabinet', 'Edit multimedia file'));
        } else {
            $jsvars['label'] = dgettext('filecabinet', 'Edit');
        }
        return javascript('open_window', $jsvars);

    }

    function deleteLink($icon=false)
    {
        $vars['mop'] = 'delete_multimedia';
        $vars['multimedia_id'] = $this->id;
        $vars['folder_id'] = $this->folder_id;
        
        $js['QUESTION'] = dgettext('filecabinet', 'Are you sure you want to delete this multimedia file?');
        $js['ADDRESS']  = PHPWS_Text::linkAddress('filecabinet', $vars, true);

        if ($icon) {
            $js['LINK'] = '<img src="images/mod/filecabinet/delete.png" />';
        } else {
            $js['LINK'] = dgettext('filecabinet', 'Delete');
        }

        return javascript('confirm', $js);
    }
    
    function getTag($embed=false) {
        
        $filter = $this->getFilter();
        $tpl['WIDTH']  = $this->width;
        $tpl['HEIGHT'] = $this->height;
         
        $is_video = $this->isVideo();

        $thumbnail = $this->thumbnailPath();

        $tpl['FILE_PATH'] = PHPWS_Core::getHomeHttp() . $this->getPath();
        $tpl['FILE_NAME'] = $this->file_name;
    
        // check for filter file
        $filter_exe = "templates/filecabinet/filters/$filter/filter.php";
        $filter_tpl = "filters/$filter.tpl";

        if ($embed) {
            if ($filter == 'media') {
                $filter_tpl = "filters/media_embed.tpl";
            } elseif ($filter == 'shockwave'){
                $filter_tpl = "filters/shockwave_embed.tpl";
            } else {

            }
        }

        if (is_file($filter_exe)) {
            include $filter_exe;
        }
        $tpl['ID'] = 'media' . $this->id;


        return PHPWS_Template::process($tpl, 'filecabinet', $filter_tpl);
    }

    function getFilter()
    {
        if ($this->embedded) {
            return $this->file_type;
        }

        switch ($this->_ext) {
        case 'flv':
        case 'mp3':
        case 'wav':
            return 'media';
            break;

        case 'qt':
        case 'mov':
            return 'quicktime';
            break;

        case 'mpeg':
        case 'mpe':
        case 'mpg':
        case 'wmv':
        case 'avi':
            return 'windows';
            break;

        case 'swf':
            $this->width = 400;
            $this->height = 400;
            return 'shockwave';
            break;
        }
    }


    function getThumbnail($css_id=null)
    {
        if (empty($css_id)) {
            $css_id = $this->id;
        }

        return sprintf('<img src="%s" title="%s" id="multimedia-thumbnail-%s" />',
                       $this->thumbnailPath(),
                       $this->title, $css_id);
    }

    function genericTN($file_name)
    {
        $this->thumbnail = $file_name . '.jpg';
        if ($this->file_type == 'application/x-shockwave-flash') {
            return @copy('images/mod/filecabinet/shockwave.jpg', $this->thumbnailDirectory() . $this->thumbnail);
        } else {
            return @copy('images/mod/filecabinet/video_generic.jpg', $this->thumbnailDirectory() . $this->thumbnail);
        }
    }

    function makeVideoThumbnail()
    {
        $thumbnail_directory = $this->thumbnailDirectory();

        if (!is_writable($thumbnail_directory)) {
            PHPWS_Error::log(FC_THUMBNAIL_NOT_WRITABLE, 'filecabinet',
                             'Multimedia::makeVideoThumbnail', $thumbnail_directory);
            return false;
        }

        $raw_file_name = $this->dropExtension();

        if (!PHPWS_Settings::get('filecabinet', 'use_ffmpeg') || 
            $this->file_type == 'application/x-shockwave-flash') {
            $this->genericTN($raw_file_name);
            return;
        } else {
            $ffmpeg_directory = PHPWS_Settings::get('filecabinet', 'ffmpeg_directory');

            if (!is_file($ffmpeg_directory . 'ffmpeg')) {
                PHPWS_Error::log(FC_FFMPREG_NOT_FOUND, 'filecabinet',
                                 'Multimedia::makeVideoThumbnail', $ffmpeg_directory);
                $this->genericTN($raw_file_name);
                return true;
            }

            $tmp_name = mt_rand();
            
            $jpeg = $raw_file_name . '.jpg';
            $thumb_path = $thumbnail_directory . $jpeg;

            $max_size = FC_THUMBNAIL_WIDTH;

            if ($this->width > $this->height) {
                $diff = $max_size / $this->width;
                $new_width = $max_size;
                $new_height = round($this->height * $diff);
            } else {
                $diff = $max_size / $this->height;
                $new_height = $max_size;
                $new_width = round($this->width * $diff);
            }

            /**
             * -i        filename
             * -an       disable audio
             * -ss       seek to position
             * -r        frame rate
             * -vframes  number of video frames to record
             * -y        overwrite output files
             * -f        force format
             */

            $command = sprintf('%sffmpeg -i %s -an -s %sx%s -ss 00:00:05 -r 1 -vframes 1 -y -f mjpeg %s',
                               $ffmpeg_directory, $this->getPath(), $new_width, $new_height, $thumb_path);
            @system($command);

            if (!is_file($thumb_path) || filesize($thumb_path) < 10) {
                @unlink($thumb_path);
                $this->genericTN($raw_file_name);
                return false;
            } else {
                $this->thumbnail = & $jpeg;
            }
        }
        return true;
    }

    function makeAudioThumbnail()
    {
        $thumbnail_directory = $this->thumbnailDirectory();

        if (!is_writable($thumbnail_directory)) {
            PHPWS_Error::log(FC_THUMBNAIL_NOT_WRITABLE, 'filecabinet',
                             'Multimedia::makeAudioThumbnail', $thumbnail_directory);

            return false;
        }

        $file_name = $this->dropExtension();
        $this->thumbnail = $file_name . '.png';
        return @copy('images/mod/filecabinet/audio.png', $thumbnail_directory . $this->thumbnail);
    }
    
    function delete()
    {
        $result = $this->commonDelete();

        if (PEAR::isError($result)) {
            return $result;
        }

        if ($this->isVideo()) {
            $tn_path = $this->thumbnailDirectory() . $this->dropExtension() . '.*';
            foreach (glob($tn_path) as $filename) {
                if (!@unlink($filename)) {
                    PHPWS_Error::log(FC_COULD_NOT_DELETE, 'filecabinet', 'PHPWS_Multimedia::delete', $filename);
                }
            }
        }

        if ($this->embedded) {
            $filename = $this->thumbnailDirectory() . $this->file_name . '.jpg';
            if (!@unlink($filename)) {
                PHPWS_Error::log(FC_COULD_NOT_DELETE, 'filecabinet', 'PHPWS_Multimedia::delete', $filename);
            }
        }

        return true;
    }

    function save($write=true, $thumbnail=true)
    {
        if (empty($this->file_directory)) {
            if ($this->folder_id) {
                $folder = new Folder($_POST['folder_id']);
                if ($folder->id) {
                    $this->setDirectory($folder->getFullDirectory());
                } else {
                    return PHPWS_Error::get(FC_MISSING_FOLDER, 'filecabinet', 'PHPWS_Multimedia::save');
                }
            } else {
                return PHPWS_Error::get(FC_DIRECTORY_NOT_SET, 'filecabinet', 'PHPWS_Multimedia::save');
            }
        }

        if ($write) {
            $result = $this->write();
            if (PEAR::isError($result)) {
                return $result;
            }
        }
        
        if (!$this->width || !$this->height) {
            $this->loadDimensions();
        }

        if ($thumbnail) {
            if ($this->isVideo()) {
                $this->makeVideoThumbnail();
            } else {
                $this->makeAudioThumbnail();
            }
        }

        if (empty($this->title)) {
            $this->title = $this->file_name;
        }

        $db = new PHPWS_DB('multimedia');
        return $db->saveObject($this);
    }

    function managerTpl($fmanager)
    {
        $tpl['ICON'] = $this->getManagerIcon($fmanager);
        $title_len = strlen($this->title);
        if ($title_len > 20) {
            $file_name = sprintf('<abbr title="%s">%s</abbr>', $this->file_name,
                                 PHPWS_Text::shortenUrl($this->file_name, 20));
        } else {
            $file_name = & $this->file_name;
        } 
        $tpl['TITLE'] = PHPWS_Text::shortenUrl($this->title, 30);

        $filename_len = strlen($this->file_name);

        if ($filename_len > 20) {
            $file_name = sprintf('<abbr title="%s">%s</abbr>', $this->file_name,
                                 PHPWS_Text::shortenUrl($this->file_name, 20));
        } else {
            $file_name = & $this->file_name;
        }
        if (!$this->embedded) {
            $tpl['INFO'] = sprintf('%s<br>%s', $file_name, $this->getSize(true));
        }
        if (Current_User::allow('filecabinet', 'edit_folders', $this->folder_id, 'folder')) {
            if (!$this->embedded) {
                $links[] = $this->editLink(true);
            }
            $links[] = $this->deleteLink(true);
            $tpl['LINKS'] = implode(' ', $links);
        }
        return $tpl;
    }

    function pinTags()
    {
        return array('TN'=>$this->getJSView(true));
    }

    function getManagerIcon($fmanager)
    {
        $vars = $fmanager->linkInfo(false);
        $vars['fop']       = 'pick_file';
        $vars['file_type'] = FC_MEDIA;
        $vars['id']        = $this->id;
        $link = PHPWS_Text::linkAddress('filecabinet', $vars, true);
        return sprintf('<a href="%s">%s</a>', $link, $this->getThumbnail());
    }

    function deleteAssoc()
    {
        $db = new PHPWS_DB('fc_file_assoc');
        $db->addWhere('file_type', FC_MEDIA);
        $db->addWhere('file_id', $this->id);
        return $db->delete();
    }

    function importExternalMedia()
    {
        PHPWS_Core::initCoreClass('XMLParser.php');
        $file_id = $this->file_name;

        include sprintf('%smod/filecabinet/inc/embed/%s.php', PHPWS_SOURCE_DIR, $this->file_type);

        $parse = new XMLParser($feed_url . $file_id, false);
        if ($parse->error) {
            PHPWS_Error::log($parse->error);
            return false;
        }
        $parse->setContentOnly(false);
        $info = $parse->format();

        if (isset($title)) {
            $this->title = eval ("return \$info$title;");
        }

        if (isset($description)) {
            $this->description = eval ("return \$info$description;");
        }

        if (isset($duration)) {
            $this->duration = eval ("return \$info$duration;");
        }

        if (isset($thumbnail)) {
            $cpy_thumb = eval ("return \$info$thumbnail;");
            $new_tn = $this->file_name . '.jpg';
            $thumb_path = $this->thumbnailDirectory() . $new_tn;

            if (@copy($cpy_thumb, $thumb_path)) {
                $this->thumbnail = $new_tn;
            } else {
                $this->genericTN();
            }
        }

        if (!empty($width) && !empty($height)) {
            $this->width = $width;
            $this->height = $height;
        }

        $this->embedded = 1;
        return true;
    }
}
?>