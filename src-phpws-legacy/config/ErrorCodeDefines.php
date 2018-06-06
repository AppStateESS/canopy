<?php

// Generic 1 - 99
$errors[PHPWS_UNKNOWN]               = 'Unknown Error.';
$errors[PHPWS_FILE_NOT_FOUND]        = 'File not found.';
$errors[PHPWS_CLASS_NOT_EXIST]       = 'Class does not exist.';
$errors[PHPWS_DIR_NOT_WRITABLE]      = 'Directory is not writable.';
$errors[PHPWS_VAR_TYPE]              = 'Wrong variable type.';
$errors[PHPWS_STRICT_TEXT]           = 'Improperly formated text.';
$errors[PHPWS_INVALID_VALUE]         = 'Invalid value.';
$errors[PHPWS_NO_MODULES]            = 'No active modules installed.';
$errors[PHPWS_WRONG_TYPE]            = 'Wrong data type.';
$errors[PHPWS_DIR_NOT_SECURE]        = 'Directories are not secure.';
$errors[PHPWS_DIR_CANT_CREATE]       = 'Unable to create file directory.';
$errors[PHPWS_WRONG_CLASS]           = 'Unknown or incorrect class.';
$errors[PHPWS_UNKNOWN_MODULE]        = 'Unknown module.';
$errors[PHPWS_CLASS_VARS]            = 'Unable to derive class variables.';
$errors[PHPWS_NO_FUNCTION]           = 'Function name not found.';
$errors[PHPWS_HUB_IDENTITY]          = 'Unable to verify source directory. Check PHPWS_SOURCE_DIR.';

// Database.php 100 - 199
$errors[PHPWS_DB_ERROR_TABLE]        = 'Table name not set.';
$errors[PHPWS_DB_NO_VALUES]          = 'No values were set before the query';
$errors[PHPWS_DB_NO_OBJ_VARS]        = 'No variables in object.';
$errors[PHPWS_DB_BAD_OP]             = 'Not an acceptable operator.';
$errors[PHPWS_DB_BAD_TABLE_NAME]     = 'Improper table name.';
$errors[PHPWS_DB_BAD_COL_NAME]       = 'Improper column name.';
$errors[PHPWS_DB_NO_COLUMN_SET]      = 'Missing column to select.';
$errors[PHPWS_DB_NOT_OBJECT]         = 'Expecting an object variable.';
$errors[PHPWS_DB_NO_VARIABLES]       = 'Class does not contain variables.';
$errors[PHPWS_DB_NO_WHERE]           = 'Function was expecting a "where" parameter.';
$errors[PHPWS_DB_NO_JOIN_DB]         = 'Join database does not exist.';
$errors[PHPWS_DB_NO_TABLE]           = 'Table does not exist.';
$errors[PHPWS_DB_NO_ID]              = 'loadObject expected the object to have an id or where clause.';
$errors[PHPWS_DB_EMPTY_SELECT]       = 'Select returned an empty result.';
$errors[PHPWS_DB_IMPORT_FAILED]      = 'Database import failed.';


// List.php 200 - 299
$errors[PHPWS_LIST_MOD_NOT_SET]       = 'Module not set.';
$errors[PHPWS_LIST_CLASS_NOT_SET]     = 'Class not set.';
$errors[PHPWS_LIST_TABLE_NOT_SET]     = 'Table not set.';
$errors[PHPWS_LIST_COLUMNS_NOT_SET]   = 'List columns not set.';
$errors[PHPWS_LIST_NAME_NOT_SET]      = 'Name not set.';
$errors[PHPWS_LIST_OP_NOT_SET]        = 'Op not set.';
$errors[PHPWS_LIST_CLASS_NOT_EXISTS]  = 'Class does not exist.';
$errors[PHPWS_LIST_NO_ITEMS_PASSED]   = 'No items passed.';
$errors[PHPWS_LIST_DB_COL_NOT_SET]    = 'Database columns not set.';


// Form.php 300 - 399
$errors[PHPWS_FORM_BAD_NAME]          = 'You may not use "%s" as a form element name.';
$errors[PHPWS_FORM_MISSING_NAME]      = 'Unable to find element "%s".';
$errors[PHPWS_FORM_MISSING_TYPE]      = 'Input type not set.';
$errors[PHPWS_FORM_WRONG_ELMT_TYPE]   = 'Wrong element type for procedure.';
$errors[PHPWS_FORM_NAME_IN_USE]       = 'Can not change name. Already in use.';
$errors[PHPWS_FORM_NO_ELEMENTS]       = 'No form elements have been created.';
$errors[PHPWS_FORM_NO_TEMPLATE]       = 'The submitted template is not an array.';
$errors[PHPWS_FORM_NO_FILE]           = 'File not found in _FILES array.';
$errors[PHPWS_FORM_UNKNOWN_TYPE]      = 'Unrecognized form type.';
$errors[PHPWS_FORM_INVALID_MATCH]     = 'Match for must be an array for a multiple input.';


// Item.php 400 - 499
$errors[PHPWS_ITEM_ID_TABLE]          = 'Id and table not set.';
$errors[PHPWS_ITEM_NO_RESULT]         = 'No result returned from database.';

// Module.php 500 - 599
$errors[PHPWS_NO_MOD_FOUND]           = 'Module not found.';

// Error.php 600 - 699
$errors[PHPWS_NO_ERROR_FILE]          = 'No error message file found.';
$errors[PHPWS_NO_MODULE]              = 'Blank module title sent to get function.';

// Help.php 700 - 799
$errors[PHPWS_UNMATCHED_OPTION]       = 'Help option not found in help configuration file.';

// File.php 800 - 899
$errors[PHPWS_FILE_WRONG_CONSTRUCT]   = 'PHPWS_File received an unknown construct.';
$errors[PHPWS_FILE_NONCLASS]          = 'Class not found.';
$errors[PHPWS_FILE_DELETE_DENIED]     = 'Unable to delete file.';
$errors[PHPWS_DIR_DELETE_DENIED]      = 'Unable to delete directory.';
$errors[PHPWS_DIR_NOT_WRITABLE]       = 'Directory is not writable.';
$errors[PHPWS_FILE_CANT_READ]         = 'Cannot read file.';
$errors[PHPWS_FILE_NO_FILES]          = 'Variable name not found in_FILES array.';
$errors[PHPWS_FILE_DIR_NONWRITE]      = 'Unable to save file in selected directory.';
$errors[PHPWS_FILE_NO_TMP]            = 'Upload directory not set in file object.';
$errors[PHPWS_FILE_SIZE]              = sprintf('Upload file size is larger than the server %s maximum.', ini_get('post_max_size'));
$errors[PHPWS_GD_ERROR]               = 'GD image libraries do not support this image type.';
$errors[PHPWS_FILE_NOT_WRITABLE]      = 'File not writable.';
$errors[PHPWS_FILE_NO_COPY]           = 'Unable to copy file.';

// Text.php 1000-1099
$errors[PHPWS_TEXT_NOT_STRING]        = 'Function expected a string variable.';

// DBPager.php 1100 - 1199
$errors[DBPAGER_NO_TOTAL_PAGES]       = 'No pages found.';
$errors[DBPAGER_MODULE_NOT_SET]       = 'Module was not set.';
$errors[DBPAGER_TEMPLATE_NOT_SET]     = 'Template was not set.';
$errors[DBPAGER_NO_TABLE]             = 'Table is blank';
$errors[DBPAGER_NO_METHOD]            = 'Method does not exist in specified class.';
$errors[DBPAGER_NO_CLASS]             = 'Class does not exist.';

// Editor.php 1200 - 1299
$errors[EDITOR_MISSING_FILE]          = 'Unable to find the specified editor type.';

// Settings.php 1300 - 1399
$errors[SETTINGS_MISSING_FILE]        = _('Unable to find your module\'s settings.php file.');

// Key.php 1400 - 1499
$errors[KEY_NOT_FOUND]                = 'Key not found.';
$errors[KEY_PERM_COLUMN_MISSING]      = 'Edit permission column does not exist.';
$errors[KEY_UNREG_FILE_MISSING]       = 'Could not find key unregister file.';
$errors[KEY_UNREG_FUNC_MISSING]       = 'Could not find key unregister function.';
$errors[KEY_RESTRICT_NO_TABLE]        = 'Key can not restrict items on phpws_key table alone.';
$errors[KEY_DUPLICATE]                = 'Duplicate key found.';

// Batch.php 1500-1599

// Cookie.php 1600-1699
$errors[COOKIE_SET_FAILED]            = 'Failed to write cookie.';
