<?php
  /**
   * @author Matthew McNaney
   * @version $Id$
   */

function filecabinet_update(&$content, $version)
{

    switch ($version) {
    case version_compare($version, '1.0.1', '<'):
        $content[] = '<pre>File Cabinet versions prior to 1.0.1 are not supported.
Please download version 1.0.2.</pre>';
        break;

    case version_compare($version, '1.0.2', '<'):
        $content[] = '<pre>';
        $db = new PHPWS_DB('folders');
        if (!$db->isTableColumn('key_id')) {
            if (PHPWS_Error::logIfError($db->addTableColumn('key_id', 'int NOT NULL default 0'))) {
                $content[] = '--- An error occurred when trying to add key_id as a column to the folders table.</pre>';
                return false;
            }
            $content[] = '--- Successfully added key_id column to folders table.';

            $db2 = new PHPWS_DB('phpws_key');
            $db2->addWhere('module', 'filecabinet');
            $db2->delete();
            $content[] = '--- Deleted false folder keys.';

            $db->reset();
            PHPWS_Core::initModClass('filecabinet', 'Folder.php');
            $result = $db->getObjects('Folder');
            if (!empty($result)) {
                foreach ($result as $folder) {
                    $folder->saveKey(true);
                }
            }
        }
        $content[] = '
1.0.2 changes
--------------
+ 1.0.0 update was missing key_id column addition to folders table.
</pre>';


    case version_compare($version, '1.1.0', '<'):
        $content[] = '<pre>';

        $home_dir = PHPWS_Boost::getHomeDir();

        if (!is_dir($home_dir . 'files/multimedia')) {
            if (is_writable($home_dir . 'files/') && @mkdir($home_dir . 'files/multimedia')) {
                $content[] = '--- "files/multimedia" directory created.';
            } else {
                $content[] = 'File Cabinet 1.1.0 requires the creation of a "multimedia" directory.
Please place it in the files/ directory.
Example: mkdir phpwebsite/files/multimedia/</pre>';
                return false;
            }
        } elseif (!is_writable($home_dir . 'files/multimedia')) {
            $content[] = 'Your files/multimedia directory is not writable by the web server.
 Please change its permissions and return.</pre>';
            return false;
        }

        if (!is_dir($home_dir . 'files/filecabinet/incoming')) {
            if (is_writable($home_dir . 'files/filecabinet') && @mkdir($home_dir . 'files/filecabinet/incoming')) {
                $content[] = '--- "files/filecabinet/incoming" directory created.';
            } else {
                $content[] = 'File Cabinet 1.1.0 is unable to create a "filecabinet/incoming" directory.
It is not required but if you want to classify files you will need to create it yourself.
Example: mkdir phpwebsite/files/filecabinet/incoming/</pre>';
                return false;
            }
        }

        $source_dir = PHPWS_SOURCE_DIR . 'mod/filecabinet/templates/filters/';
        $dest_dir   = $home_dir . 'templates/filecabinet/filters/';

        if (!is_dir($dest_dir)) {
            if (!PHPWS_File::copy_directory($source_dir, $dest_dir)) {
                $content[] = '--- FAILED copying templates/filters/ directory locally.</pre>';
                return false;
            }
        }

        $files = array('templates/manager/pick.tpl', 'templates/classify_file.tpl', 
                       'templates/classify_list.tpl', 'templates/image_edit.tpl', 
                       'templates/multimedia_edit.tpl', 'templates/multimedia_grid.tpl',
                       'templates/style.css', 'templates/settings.tpl', 'conf/config.php');
        
        if (PHPWS_Boost::updateFiles($files, 'filecabinet')) {
            $content[] = '--- Copied the following files:';
        } else {
            $content[] = '--- FAILED copying the following files:';
        }

        $content[] = "    " . implode("\n    ", $files);

        $db = new PHPWS_DB('images');
        if (!$db->isTableColumn('parent_id')) {
            if (PHPWS_Error::logIfError($db->addTableColumn('parent_id', 'int NOT NULL default 0'))) {
                $content[] = 'Could not create parent_id column in images table.</pre>';
                return false;
            }
        }

        if (!$db->isTableColumn('url')) {
            if (PHPWS_Error::logIfError($db->addTableColumn('url', 'varchar(255) NULL'))) {
                $content[] = 'Could not create url column in images table.</pre>';
                return false;
            }
        }

       if (!PHPWS_DB::isTable('multimedia')) {
            $result = PHPWS_DB::importFile(PHPWS_SOURCE_DIR . 'mod/filecabinet/boost/multimedia.sql');
            if (!PHPWS_Error::logIfError($result)) {
                $content[] = '--- Multimedia table created successfully.';
            } else {
                $content[] = '--- Failed to create multimedia table.</pre>';
                return false;
            }
        }
        
        $content[] = '
1.1.0 changes
--------------
+ Fixed authorized check when unpinning folders
+ Images can now be linked to other pages.
+ Resized images can now be linked to their parent image.
+ Clip option moved outside edit_folder permissions when viewing images.
+ Added writable directory check before allowing new folders to be
  created.
+ Fixed some error messages in File_Common.
+ Commented out ext variable in File_Common. Doesn\'t appear to be in
  use.
+ Created setDirectory function for File_Common. Assures trailing
  forward slash on directory name.
+ Removed itemname variable from Document_Manager
+ Added ability to classify uploaded files.
+ New folder class - Multimedia
+ Multimedia files can be clipped and pasted via SmartTags.
</pre>
';

    case version_compare($version, '1.2.0', '<'):
        $content[] = '<pre>';
        $files = array('img/no_image.png', 'conf/config.php', 'conf/video_types.php',
                       'conf/embedded.php',
                       'javascript/folder_contents/head.js',
                       'javascript/clear_image/head.js',
                       'javascript/clear_image/body.js',
                       'javascript/pick_image/head.js',
                       'templates/image_folders.tpl', 'templates/settings.tpl',
                       'templates/style.css', 'templates/image_view.tpl',
                       'templates/multimedia_view.tpl', 'templates/style.css',
                       'img/video_generic.png', 'templates/image_edit.tpl', 'conf/error.php');

        if (PHPWS_Boost::updateFiles($files, 'filecabinet')) {
            $content[] = '--- Copied the following files:';
        } else {
            $content[] = '--- FAILED copying the following files:';
        }

        $content[] = "    " . implode("\n    ", $files);
        
 $content[] = '
1.2.0 changes
--------------
+ Each folder tab now checks the write status of each directory
  separately.
+ Added multimedia folders, file types, icons and ability to playback.
+ Folder now loads files in filename order.
+ Added checkbox that allows you to hide child images
+ Deleting a parent image makes all child images parents.
+ Changed wording on image linking to urls
+ File Cabinet\'s Image Manager no longer shows small thumbnail.
  Instead, it shows a full image set to the current dimension limits.
+ Image - getTag function now allows an "id" parameter that will be
          added to the image tag (i.e. id="css-id-name")
+ Changed "no image chosen" graphic.
+ Added missing new columns to image table in install.sql
+ Added ability to delete incoming files.
+ Added directory permission checks to classify.
+ Classify directory can now be set in fc settings.
+ Created classify override in config.php file.
+ Option to use ffmpeg to create thumbnails
</pre>
';

    }

    return true;
}


?>