<?php

/*********************** TEMPLATES *****************************/
/**
 * Setting FORCE_THEME_TEMPLATES to TRUE forces the template class
 * to ONLY look for template files in your current theme. When FALSE
 * the template class will first look in your theme then in the 
 * templates/ directory. When FALSE, the template class has to make
 * sure the file is in the theme. If you know for sure, it is then
 * setting this to TRUE will save a file check.
 */

define("FORCE_THEME_TEMPLATES", FALSE);

/**
 * phpWebSite uses templates from the templates directory by default.
 * This makes sense for ordering purposes and to make branches load
 * faster.
 * However if you are developing, you may not want it too. In that
 * case you can force the core to pull module templates from the
 * module template directory directly. Set the below to TRUE if
 * this is the case.
 */

define("FORCE_MOD_TEMPLATES", TRUE);

/**
 * Normally, if the the Pear template class can't fill in at least one
 * tag in a template, it will return NULL. Setting the below to TRUE,
 * causes the phpWebSite to still return template WITHOUT the tag
 * substitutions. This should normally be set to FALSE unless you are
 * testing code.
 */

define("RETURN_BLANK_TEMPLATES", TRUE);

/**
 * If you want template to prefix the templates it is using with an
 * information tag, set the below to TRUE.
 */

define("LABEL_TEMPLATES", FALSE);
?>