<?php
/**
 * Class for individual pages within volumes
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @version $Id$
 */

class Webpage_Page {
    var $id          = 0;
    // Id of volume page belongs to
    var $volume_id   = 0;
    var $title       = NULL;
    var $content     = NULL;
    var $page_number = NULL;
    var $template    = NULL;
    var $_error      = NULL;
    var $_volume     = NULL;

    function Webpage_Page($id=NULL)
    {
        if (empty($id)) {
            return;
        }

        $this->id = (int)$id;
        $this->init();
    }

    function init()
    {
        $db = & new PHPWS_DB('webpage_page');
        $result = $db->loadObject($this);
        if (PEAR::isError($result)) {
            $this->_error = $result;
            return;
        }

        if (empty($this->_volume)) {
            $this->loadVolume();
        }

    }

    function loadVolume()
    {
        $this->_volume = & new Webpage_Volume($this->volume_id);
    }

    function setTitle($title)
    {
        $this->title = strip_tags($title);
    }

    function setContent($content)
    {
        $this->content = PHPWS_Text::parseInput($content);
    }


    function getTemplateDirectory()
    {
        if (FORCE_MOD_TEMPLATES) {
            $directory = PHPWS_SOURCE_DIR . 'mod/webpage/templates/page/';
        } else {
            $directory = 'templates/webpage/page/';
        }
        return $directory;
    }

    function getContent()
    {
        return PHPWS_Text::parseOutput($this->content);
    }

    function getTemplateList()
    {
        $directory = $this->getTemplateDirectory();

        $files = scandir($directory);
        if (empty($files)) {
            return NULL;
        }

        foreach ($files as $key => $f_name) {
            if (preg_match('/\.tpl$/i', $f_name)) {
                $file_list[$f_name] = $f_name;
            }
        }
        if (!isset($file_list)) {
            return NULL;
        } else {
            return $file_list;
        }
    }

    function checkTemplate()
    {
        $directory = $this->getTemplateDirectory() . $this->template;
        return is_file($directory);
    }


    function post()
    {
        if (empty($_POST['volume_id'])) {
            exit('missing volume id. better error here');
        }

        $this->volume_id = (int)$_POST['volume_id'];

        if (empty($_POST['title'])) {
            $this->title = NULL;
        } else {
            $this->setTitle($_POST['title']);
        }

        if (empty($_POST['content'])) {
            $errors[] = _('Missing page content.');
        } else {
            $this->setContent($_POST['content']);
        }

        if (empty($_POST['template'])) {
            return PHPWS_Error(WP_MISSING_TEMPLATE, 'webpage', 'Page::post');
        }

        $this->template = strip_tags($_POST['template']);

        if (isset($errors)) {
            return $errors;
        } else {
            return TRUE;
        }
    }

    function getTplTags($admin=FALSE)
    {
        $template['TITLE'] = $this->title;
        $template['CONTENT'] = $this->getContent();
        $template['CURRENT_PAGE'] = $this->page_number;

        if (Current_User::allow('webpage', 'edit_page', $this->id)) {
            $vars = array('wp_admin'  => 'edit_page',
                          'page_id'   => $this->id,
                          'volume_id' => $this->volume_id);

            $links[] = PHPWS_Text::secureLink(_('Edit'), 'webpage', $vars);

            if ($admin) {
                $jsvar['QUESTION'] = _('Are you sure you want to remove this page?');
                $jsvar['ADDRESS'] = sprintf('index.php?module=webpage&wp_admin=delete_page&page_id=%s&volume_id=%s&authkey=%s',
                                            $this->id, $this->volume_id, Current_User::getAuthKey());
                $jsvar['LINK'] = ('Delete');

                $links[] = javascript('confirm', $jsvar);
            }
            $template['ADMIN_LINKS'] = implode(' | ', $links);
        }

        if (!empty($this->_volume)) {
            $header_tags = $this->_volume->getTplTags(!$admin);
            $template = array_merge($template, $header_tags);
        }
        return $template;
    }

    function getPageLink($verbose=FALSE)
    {
        $id = $this->_volume->id;
        $page = (int)$this->page_number;

        if ($verbose) {
            $address = $this->page_number . ' ' . $this->title;
        } else {
            $address = $this->page_number;
        }

        return PHPWS_Text::rewriteLink($address, 'webpage', $id, $page);
    }

    function getPageUrl()
    {
        if (MOD_REWRITE_ENABLED) {
            return sprintf('webpage/%s/%s', $this->volume_id, $this->page_number);
        } else {
            return sprintf('index.php?module=webpage&amp;id=%s&amp;page=%s', $this->volume_id, $this->page_number);
        }
    }

    function view($admin=FALSE)
    {
        $template = $this->getTplTags($admin);

        if (!is_file($this->getTemplateDirectory() . $this->template)) {
            return implode('<br />', $template);
        }

        $this->_volume->flagKey();

        return PHPWS_Template::process($template, 'webpage', 'page/' . $this->template);
    }

    function delete()
    {
        $db = & new PHPWS_DB('webpage_page');
        $db->addWhere('id', $this->id);
        return $db->delete();
    }

    function save()
    {
        if (empty($this->volume_id)) {
            return FALSE;
        }

        $volume = & new Webpage_Volume($this->volume_id);
        
        if (empty($this->content)) {
            return FALSE;
        }

        if (!$this->checkTemplate()) {
            return PHPWS_Error::get(WP_TPL_FILE_MISSING, 'webpages', 'Webpage_Page::save');
        }

        if (empty($this->page_number)) {
            $this->page_number = count($volume->_pages) + 1;
        }

        $db = & new PHPWS_DB('webpage_page');
        $result = $db->saveObject($this);

        if (!PEAR::isError($result)) {
            $search = & new Search($volume->key_id);
            $search->addKeywords($this->title . ' ' .$this->content);
            $sResult = $search->save();
        }
        return $result;
    }

}


?>