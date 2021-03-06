<?php

// This file defines settingpages and externalpages under the "appearance" category

if ($hassiteconfig) { // speedup for non-admins, add all caps used on this page

    $ADMIN->add('appearance', new admin_category('themes', new lang_string('themes')));
    // "themesettings" settingpage
    $temp = new admin_settingpage('themesettings', new lang_string('themesettings', 'admin'));
    $temp->add(new admin_setting_configtext('themelist', new lang_string('themelist', 'admin'), new lang_string('configthemelist','admin'), '', PARAM_NOTAGS));
    $setting = new admin_setting_configcheckbox('themedesignermode', new lang_string('themedesignermode', 'admin'), new lang_string('configthemedesignermode', 'admin'), 0);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);
    $temp->add(new admin_setting_configcheckbox('allowuserthemes', new lang_string('allowuserthemes', 'admin'), new lang_string('configallowuserthemes', 'admin'), 0));
    $temp->add(new admin_setting_configcheckbox('allowcoursethemes', new lang_string('allowcoursethemes', 'admin'), new lang_string('configallowcoursethemes', 'admin'), 0));
    $temp->add(new admin_setting_configcheckbox('allowcategorythemes',  new lang_string('allowcategorythemes', 'admin'), new lang_string('configallowcategorythemes', 'admin'), 0));
    $temp->add(new admin_setting_configcheckbox('allowthemechangeonurl',  new lang_string('allowthemechangeonurl', 'admin'), new lang_string('configallowthemechangeonurl', 'admin'), 0));
    $temp->add(new admin_setting_configcheckbox('allowuserblockhiding', new lang_string('allowuserblockhiding', 'admin'), new lang_string('configallowuserblockhiding', 'admin'), 1));
    $temp->add(new admin_setting_configcheckbox('allowblockstodock', new lang_string('allowblockstodock', 'admin'), new lang_string('configallowblockstodock', 'admin'), 1));
    $temp->add(new admin_setting_configtextarea('custommenuitems', new lang_string('custommenuitems', 'admin'), new lang_string('configcustommenuitems', 'admin'), '', PARAM_TEXT, '50', '10'));
    $temp->add(new admin_setting_configcheckbox('enabledevicedetection', new lang_string('enabledevicedetection', 'admin'), new lang_string('configenabledevicedetection', 'admin'), 1));
    $temp->add(new admin_setting_devicedetectregex('devicedetectregex', new lang_string('devicedetectregex', 'admin'), new lang_string('devicedetectregex_desc', 'admin'), ''));
    $ADMIN->add('themes', $temp);
    $ADMIN->add('themes', new admin_externalpage('themeselector', new lang_string('themeselector','admin'), $CFG->wwwroot . '/theme/index.php'));

    // settings for each theme
    foreach (get_plugin_list('theme') as $theme => $themedir) {
        $settings_path = "$themedir/settings.php";
        if (file_exists($settings_path)) {
            $settings = new admin_settingpage('themesetting'.$theme, new lang_string('pluginname', 'theme_'.$theme));
            include($settings_path);
            if ($settings) {
                $ADMIN->add('themes', $settings);
            }
        }
    }


    // calendar
    $temp = new admin_settingpage('calendar', new lang_string('calendarsettings','admin'));
    $temp->add(new admin_setting_special_adminseesall());
    //this is hacky because we do not want to include the stuff from calendar/lib.php
    $temp->add(new admin_setting_configselect('calendar_site_timeformat', new lang_string('pref_timeformat', 'calendar'),
                                              new lang_string('explain_site_timeformat', 'calendar'), '0',
                                              array('0'        => new lang_string('default', 'calendar'),
                                                    '%I:%M %p' => new lang_string('timeformat_12', 'calendar'),
                                                    '%H:%M'    => new lang_string('timeformat_24', 'calendar'))));
    $temp->add(new admin_setting_configselect('calendar_startwday', new lang_string('configstartwday', 'admin'), new lang_string('helpstartofweek', 'admin'), 0,
    array(
            0 => new lang_string('sunday', 'calendar'),
            1 => new lang_string('monday', 'calendar'),
            2 => new lang_string('tuesday', 'calendar'),
            3 => new lang_string('wednesday', 'calendar'),
            4 => new lang_string('thursday', 'calendar'),
            5 => new lang_string('friday', 'calendar'),
            6 => new lang_string('saturday', 'calendar')
        )));
    $temp->add(new admin_setting_special_calendar_weekend());
    $options = array();
    for ($i=1; $i<=99; $i++) {
        $options[$i] = $i;
    }
    $temp->add(new admin_setting_configselect('calendar_lookahead',new lang_string('configlookahead','admin'),new lang_string('helpupcominglookahead', 'admin'),21,$options));
    $options = array();
    for ($i=1; $i<=20; $i++) {
        $options[$i] = $i;
    }
    $temp->add(new admin_setting_configselect('calendar_maxevents',new lang_string('configmaxevents','admin'),new lang_string('helpupcomingmaxevents', 'admin'),10,$options));
    $temp->add(new admin_setting_configcheckbox('enablecalendarexport', new lang_string('enablecalendarexport', 'admin'), new lang_string('configenablecalendarexport','admin'), 1));
    $temp->add(new admin_setting_configtext('calendar_exportsalt', new lang_string('calendarexportsalt','admin'), new lang_string('configcalendarexportsalt', 'admin'), random_string(60)));
    $ADMIN->add('appearance', $temp);

    // blog
    $temp = new admin_settingpage('blog', new lang_string('blog','blog'));
    $temp->add(new admin_setting_configcheckbox('useblogassociations', new lang_string('useblogassociations', 'blog'), new lang_string('configuseblogassociations','blog'), 1));
    $temp->add(new admin_setting_bloglevel('bloglevel', new lang_string('bloglevel', 'admin'), new lang_string('configbloglevel', 'admin'), 4, array(BLOG_GLOBAL_LEVEL => new lang_string('worldblogs','blog'),
                                                                                                                                           BLOG_SITE_LEVEL => new lang_string('siteblogs','blog'),
                                                                                                                                           BLOG_USER_LEVEL => new lang_string('personalblogs','blog'),
                                                                                                                                           0 => new lang_string('disableblogs','blog'))));
    $temp->add(new admin_setting_configcheckbox('useexternalblogs', new lang_string('useexternalblogs', 'blog'), new lang_string('configuseexternalblogs','blog'), 1));
    $temp->add(new admin_setting_configselect('externalblogcrontime', new lang_string('externalblogcrontime', 'blog'), new lang_string('configexternalblogcrontime', 'blog'), 86400,
        array(43200 => new lang_string('numhours', '', 12),
              86400 => new lang_string('numhours', '', 24),
              172800 => new lang_string('numdays', '', 2),
              604800 => new lang_string('numdays', '', 7))));
    $temp->add(new admin_setting_configtext('maxexternalblogsperuser', new lang_string('maxexternalblogsperuser','blog'), new lang_string('configmaxexternalblogsperuser', 'blog'), 1));
    $temp->add(new admin_setting_configcheckbox('blogusecomments', new lang_string('enablecomments', 'admin'), new lang_string('configenablecomments', 'admin'), 1));
    $temp->add(new admin_setting_configcheckbox('blogshowcommentscount', new lang_string('showcommentscount', 'admin'), new lang_string('configshowcommentscount', 'admin'), 1));
    $ADMIN->add('appearance', $temp);

    // Navigation settings
    $temp = new admin_settingpage('navigation', new lang_string('navigation'));
    $choices = array(
        HOMEPAGE_SITE => new lang_string('site'),
        HOMEPAGE_MY => new lang_string('mymoodle', 'admin'),
        HOMEPAGE_USER => new lang_string('userpreference', 'admin')
    );
    $temp->add(new admin_setting_configselect('defaulthomepage', new lang_string('defaulthomepage', 'admin'), new lang_string('configdefaulthomepage', 'admin'), HOMEPAGE_SITE, $choices));
    $temp->add(new admin_setting_configcheckbox('navshowcategories', new lang_string('navshowcategories', 'admin'), new lang_string('confignavshowcategories', 'admin'), 1));
    $temp->add(new admin_setting_configcheckbox('navshowmycoursecategories', new lang_string('navshowmycoursecategories', 'admin'), new lang_string('navshowmycoursecategories_help', 'admin'), 0));
    $temp->add(new admin_setting_configcheckbox('navshowallcourses', new lang_string('navshowallcourses', 'admin'), new lang_string('confignavshowallcourses', 'admin'), 0));
    $temp->add(new admin_setting_configtext('navcourselimit',new lang_string('navcourselimit','admin'),new lang_string('confignavcourselimit', 'admin'),20,PARAM_INT));
    $temp->add(new admin_setting_configcheckbox('usesitenameforsitepages', new lang_string('usesitenameforsitepages', 'admin'), new lang_string('configusesitenameforsitepages', 'admin'), 0));
    $temp->add(new admin_setting_configcheckbox('linkadmincategories', new lang_string('linkadmincategories', 'admin'), new lang_string('linkadmincategories_help', 'admin'), 0));
    $temp->add(new admin_setting_configcheckbox('navshowfrontpagemods', new lang_string('navshowfrontpagemods', 'admin'), new lang_string('navshowfrontpagemods_help', 'admin'), 1));
    $temp->add(new admin_setting_configcheckbox('navadduserpostslinks', new lang_string('navadduserpostslinks', 'admin'), new lang_string('navadduserpostslinks_help', 'admin'), 1));

    $ADMIN->add('appearance', $temp);

    // "htmlsettings" settingpage
    $temp = new admin_settingpage('htmlsettings', new lang_string('htmlsettings', 'admin'));
    $temp->add(new admin_setting_configcheckbox('formatstringstriptags', new lang_string('stripalltitletags', 'admin'), new lang_string('configstripalltitletags', 'admin'), 1));
    $temp->add(new admin_setting_emoticons());
    $ADMIN->add('appearance', $temp);
    $ADMIN->add('appearance', new admin_externalpage('resetemoticons', new lang_string('emoticonsreset', 'admin'),
        new moodle_url('/admin/resetemoticons.php'), 'moodle/site:config', true));

    // "documentation" settingpage
    $temp = new admin_settingpage('documentation', new lang_string('moodledocs'));
    $temp->add(new admin_setting_configtext('docroot', new lang_string('docroot', 'admin'), new lang_string('configdocroot', 'admin'), 'http://docs.moodle.org', PARAM_URL));
    $temp->add(new admin_setting_configcheckbox('doctonewwindow', new lang_string('doctonewwindow', 'admin'), new lang_string('configdoctonewwindow', 'admin'), 0));
    $ADMIN->add('appearance', $temp);

    $temp = new admin_externalpage('mypage', new lang_string('mypage', 'admin'), $CFG->wwwroot . '/my/indexsys.php');
    $ADMIN->add('appearance', $temp);

    $temp = new admin_externalpage('profilepage', new lang_string('myprofile', 'admin'), $CFG->wwwroot . '/user/profilesys.php');
    $ADMIN->add('appearance', $temp);

    // coursecontact is the person responsible for course - usually manages enrolments, receives notification, etc.
    $temp = new admin_settingpage('coursecontact', new lang_string('courses'));
    $temp->add(new admin_setting_special_coursecontact());
    $temp->add(new admin_setting_configcheckbox('courselistshortnames',
            new lang_string('courselistshortnames', 'admin'),
            new lang_string('courselistshortnames_desc', 'admin'), 0));
    $ADMIN->add('appearance', $temp);

    $temp = new admin_settingpage('ajax', new lang_string('ajaxuse'));
    $temp->add(new admin_setting_configcheckbox('enableajax', new lang_string('enableajax', 'admin'), new lang_string('configenableajax', 'admin'), 1));
    $temp->add(new admin_setting_configcheckbox('useexternalyui', new lang_string('useexternalyui', 'admin'), new lang_string('configuseexternalyui', 'admin'), 0));
    $temp->add(new admin_setting_configcheckbox('yuicomboloading', new lang_string('yuicomboloading', 'admin'), new lang_string('configyuicomboloading', 'admin'), 1));
    $setting = new admin_setting_configcheckbox('cachejs', new lang_string('cachejs', 'admin'), new lang_string('cachejs_help', 'admin'), 1);
    $setting->set_updatedcallback('js_reset_all_caches');
    $temp->add($setting);
    $temp->add(new admin_setting_configcheckbox('enablecourseajax', new lang_string('enablecourseajax', 'admin'),
                                                new lang_string('enablecourseajax_desc', 'admin'), 1));
    $ADMIN->add('appearance', $temp);

    // link to tag management interface
    $ADMIN->add('appearance', new admin_externalpage('managetags', new lang_string('managetags', 'tag'), "$CFG->wwwroot/tag/manage.php"));

    $temp = new admin_settingpage('additionalhtml', new lang_string('additionalhtml', 'admin'));
    $temp->add(new admin_setting_heading('additionalhtml_heading', new lang_string('additionalhtml_heading', 'admin'), new lang_string('additionalhtml_desc', 'admin')));
    $temp->add(new admin_setting_configtextarea('additionalhtmlhead', new lang_string('additionalhtmlhead', 'admin'), new lang_string('additionalhtmlhead_desc', 'admin'), '', PARAM_RAW));
    $temp->add(new admin_setting_configtextarea('additionalhtmltopofbody', new lang_string('additionalhtmltopofbody', 'admin'), new lang_string('additionalhtmltopofbody_desc', 'admin'), '', PARAM_RAW));
    $temp->add(new admin_setting_configtextarea('additionalhtmlfooter', new lang_string('additionalhtmlfooter', 'admin'), new lang_string('additionalhtmlfooter_desc', 'admin'), '', PARAM_RAW));
    $ADMIN->add('appearance', $temp);

} // end of speedup

