<?php
/**
 * DokuWiki Bibliography Plugin Datasource management (Admin Component)
 *
 * @license MIT
 * @author  Samuel Gyger <samuel@gyger.tech>
 */

// must be run within Dokuwiki
use dokuwiki\plugin\bibliography\meta as Plugin;

if (!defined('DOKU_INC')) die();

class admin_plugin_bibliography_datasource extends \DokuWiki_Admin_Plugin {

    public function __construct() {

    }

    public function getMenuSort() {
        return 555;
    }

    public function handle() {
        if($_REQUEST['step'] && !checkSecurityToken()) {
            unset($_REQUEST['step']);
        }
    }

    public function html() {
        $abrt = false;
        $next = false;

        echo $this->locale_xhtml('sources_overview');

        echo '<ul class="tabs" id="plugin__bibliography_tabs">';
        echo '<li class="active"><a href="#plugin__bibliography_sources">' . $this->getLang('tab_edit') . '</a></li>';
        echo '<li><a href="#plugin__bibliography_entries">' . $this->getLang('tab_entries') . '</a></li>';
        echo '</ul>';
        echo '<div class="panelHeader"></div>';

        $editor = new Plugin\data\SourceEditor();
        echo $editor->getEditor();

    }

    /**
     * Display a progress bar of all steps
     *
     * @param string $next the next step
     */
    private function _progress($next) {
        $steps  = array('version', 'download', 'unpack', 'check', 'upgrade');
        $active = true;
        $count = 0;

        echo '<div id="plugin__upgrade_meter"><ol>';
        foreach($steps as $step) {
            $count++;
            if($step == $next) $active = false;
            if($active) {
                echo '<li class="active">';
                echo '<span class="step">✔</span>';
            } else {
                echo '<li>';
                echo '<span class="step">'.$count.'</span>';
            }

            echo '<span class="stage">'.$this->getLang('step_'.$step).'</span>';
            echo '</li>';
        }
        echo '</ol></div>';
    }

    /**
     * Decides the current step and executes it
     *
     * @param bool $abrt
     * @param bool $next
     */
    private function _stepit(&$abrt, &$next) {

        if(isset($_REQUEST['step']) && is_array($_REQUEST['step'])) {
            $step = array_shift(array_keys($_REQUEST['step']));
        } else {
            $step = '';
        }

        if($step == 'cancel' || $step == 'done') {
            # cleanup
            @unlink($this->tgzfile);
            $this->_rdel($this->tgzdir);
            if($step == 'cancel') $step = '';
        }

        if($step) {
            $abrt = true;
            $next = false;
            if($step == 'version') {
                $this->_step_version();
                $next = 'download';
            } elseif ($step == 'done') {
                $this->_step_done();
                $next = '';
                $abrt = '';
            } elseif(!file_exists($this->tgzfile)) {
                if($this->_step_download()) $next = 'unpack';
            } elseif(!is_dir($this->tgzdir)) {
                if($this->_step_unpack()) $next = 'check';
            } elseif($step != 'upgrade') {
                if($this->_step_check()) $next = 'upgrade';
            } elseif($step == 'upgrade') {
                if($this->_step_copy()) {
                    $next = 'done';
                    $abrt = '';
                }
            } else {
                echo 'uhm. what happened? where am I? This should not happen';
            }
        } else {
            # first time run, show intro
            echo $this->locale_xhtml('step0');
            $abrt = false;
            $next = 'version';
        }
    }

    /**
     * Output the given arguments using vsprintf and flush buffers
     */
    public static function _say() {
        $args = func_get_args();
        echo '<img src="'.DOKU_BASE.'lib/images/blank.gif" width="16" height="16" alt="" /> ';
        echo vsprintf(array_shift($args)."<br />\n", $args);
        flush();
        ob_flush();
    }

    /**
     * Print a warning using the given arguments with vsprintf and flush buffers
     */
    public function _warn() {
        $this->haderrors = true;

        $args = func_get_args();
        echo '<img src="'.DOKU_BASE.'lib/images/error.png" width="16" height="16" alt="!" /> ';
        echo vsprintf(array_shift($args)."<br />\n", $args);
        flush();
        ob_flush();
    }

    /**
     * Check various versions
     *
     * @return bool
     */
    private function _step_version() {
        $ok = true;

        // we need SSL - only newer HTTPClients check that themselves
        if(!in_array('ssl', stream_get_transports())) {
            $this->_warn($this->getLang('vs_ssl'));
            $ok = false;
        }

        // get the available version
        $http       = new DokuHTTPClient();
        $tgzversion = $http->get($this->tgzversion);
        if(!$tgzversion) {
            $this->_warn($this->getLang('vs_tgzno').' '.hsc($http->error));
            $ok = false;
        }
        $tgzversionnum = $this->dateFromVersion($tgzversion);
        if($tgzversionnum === 0) {
            $this->_warn($this->getLang('vs_tgzno'));
            $ok            = false;
        } else {
            self::_say($this->getLang('vs_tgz'), $tgzversion);
        }

        // get the current version
        $version = getVersion();
        $versionnum = $this->dateFromVersion($version);
        self::_say($this->getLang('vs_local'), $version);

        // compare versions
        if(!$versionnum) {
            $this->_warn($this->getLang('vs_localno'));
            $ok = false;
        } else if($tgzversionnum) {
            if($tgzversionnum < $versionnum) {
                $this->_warn($this->getLang('vs_newer'));
                $ok = false;
            } elseif($tgzversionnum == $versionnum && $tgzversion == $version) {
                $this->_warn($this->getLang('vs_same'));
                $ok = false;
            }
        }

        // check plugin version
        $pluginversion = $http->get($this->pluginversion);
        if($pluginversion) {
            $plugininfo = linesToHash(explode("\n", $pluginversion));
            $myinfo     = $this->getInfo();
            if($plugininfo['date'] > $myinfo['date']) {
                $this->_warn($this->getLang('vs_plugin'), $plugininfo['date']);
                $ok = false;
            }
        }

        // check if PHP is up to date
        $minphp = '5.6';
        if(version_compare(phpversion(), $minphp, '<')) {
            $this->_warn($this->getLang('vs_php'), $minphp, phpversion());
            $ok = false;
        }

        return $ok;
    }

    /**
     * Redirect to the start page
     */
    private function _step_done() {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        echo $this->getLang('finish');
        echo "<script type='text/javascript'>location.href='".DOKU_URL."';</script>";
    }

    /**
     * Download the tarball
     *
     * @return bool
     */
    private function _step_download() {
        self::_say($this->getLang('dl_from'), $this->tgzurl);

        @set_time_limit(300);
        @ignore_user_abort();

        $http          = new DokuHTTPClient();
        $http->timeout = 300;
        $data          = $http->get($this->tgzurl);

        if(!$data) {
            $this->_warn($http->error);
            $this->_warn($this->getLang('dl_fail'));
            return false;
        }

        if(!io_saveFile($this->tgzfile, $data)) {
            $this->_warn($this->getLang('dl_fail'));
            return false;
        }

        self::_say($this->getLang('dl_done'), filesize_h(strlen($data)));

        return true;
    }

    /**
     * Unpack the tarball
     *
     * @return bool
     */
    private function _step_unpack() {
        self::_say('<b>'.$this->getLang('pk_extract').'</b>');

        @set_time_limit(300);
        @ignore_user_abort();

        try {
            $tar = new VerboseTar();
            $tar->open($this->tgzfile);
            $tar->extract($this->tgzdir, 1);
            $tar->close();
        } catch (Exception $e) {
            $this->_warn($e->getMessage());
            $this->_warn($this->getLang('pk_fail'));
            return false;
        }

        self::_say($this->getLang('pk_done'));

        self::_say(
            $this->getLang('pk_version'),
            hsc(file_get_contents($this->tgzdir.'/VERSION')),
            getVersion()
        );
        return true;
    }

    /**
     * Check permissions of files to change
     *
     * @return bool
     */
    private function _step_check() {
        self::_say($this->getLang('ck_start'));
        $ok = $this->_traverse('', true);
        if($ok) {
            self::_say('<b>'.$this->getLang('ck_done').'</b>');
        } else {
            $this->_warn('<b>'.$this->getLang('ck_fail').'</b>');
        }
        return $ok;
    }

    /**
     * Copy over new files
     *
     * @return bool
     */
    private function _step_copy() {
        self::_say($this->getLang('cp_start'));
        $ok = $this->_traverse('', false);
        if($ok) {
            self::_say('<b>'.$this->getLang('cp_done').'</b>');
            $this->_rmold();
            self::_say('<b>'.$this->getLang('finish').'</b>');
        } else {
            $this->_warn('<b>'.$this->getLang('cp_fail').'</b>');
        }
        return $ok;
    }
}
