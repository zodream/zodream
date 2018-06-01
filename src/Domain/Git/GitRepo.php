<?php
namespace Zodream\Domain\Git;
/**
 * Git Repository Interface Class
 *
 * This class enables the creating, reading, and manipulation
 * of a git repository
 *
 * @class  GitRepo
 * @package    Git.php
 * @version    0.1.4
 * @author     James Brumond
 * @copyright  Copyright 2013 James Brumond
 * @repo       http://github.com/kbjr/Git.php
 */
use Exception;
use Zodream\Infrastructure\Support\Process;

class GitRepo {

    protected $repoPath = null;
    protected $bare = false;
    protected $envOpts = array();

    /**
     * Create a new git repository
     *
     * Accepts a creation path, and, optionally, a source path
     *
     * @access  public
     * @param $repoPath
     * @param null $source
     * @param bool $remoteSource
     * @param null $reference
     * @return GitRepo
     * @throws Exception
     * @internal param repository $string path
     * @internal param directory $string to source
     * @internal param reference $string path
     */
    public static function &createNew(
        $repoPath,
        $source = null,
        $remoteSource = false,
        $reference = null
    ) {
        if (is_dir($repoPath) &&
            is_dir($repoPath.'/.git')) {
            throw new Exception(
                __('"{path}" is already a git repository', [
                    'path' => $repoPath
                ])
            );
        }
        $repo = new static($repoPath, true, false);
        if (!is_string($source)) {
            $repo->run('init');
            return $repo;
        }
        if (!$remoteSource) {
            $repo->cloneFrom($source);
            return $repo;
        }
        if (!is_dir($reference) || !is_dir($reference.'/.git')) {
            throw new Exception(
                __('"{reference}" is not a git repository. Cannot use as reference.', [
                    'reference' => $reference
                ])
            );
        }
        if (strlen($reference)) {
            $reference = realpath($reference);
            $reference = "--reference $reference";
        }
        $repo->cloneRemote($source, $reference);
        return $repo;
    }

    /**
     * Constructor
     *
     * Accepts a repository path
     *
     * @access  public
     * @param   string $repoPath repository path
     * @param bool $createNew
     * @param bool $init
     * @internal param create $bool if not exists?
     */
    public function __construct($repoPath = null, $createNew = false, $init = true) {
        if (is_string($repoPath)) {
            $this->setRepoPath($repoPath, $createNew, $init);
        }
    }

    /**
     * Set the repository's path
     *
     * Accepts the repository path
     *
     * @access  public
     * @param   string $repoPath repository path
     * @param bool $createNew
     * @param bool $init
     * @throws Exception
     * @internal param create $bool if not exists?
     * @internal param initialize $bool new Git repo if not exists?
     */
    public function setRepoPath($repoPath, $createNew = false, $init = true) {
        if (!is_string($repoPath)) {
            return;
        }
        if ($newPath = realpath($repoPath)) {
            $repoPath = $newPath;
            if (!is_dir($repoPath)) {
                throw new Exception(
                    __('"{path}" is not a directory', [
                        'path' => $repoPath
                    ])
                );
            }
            // Is this a work tree?
            if (is_dir($repoPath.'/.git')) {
                $this->repoPath = $repoPath;
                $this->bare = false;
                // Is this a bare repo?
                return;
            }
            if (is_file($repoPath.'/config')) {
                $parseIni = parse_ini_file($repoPath.'/config');
                if ($parseIni['bare']) {
                    $this->repoPath = $repoPath;
                    $this->bare = true;
                }
                return;
            }
            if (!$createNew) {
                throw new Exception(
                    __('"{path}" is not a git repository', [
                        'path' => $repoPath
                    ])
                );
            }
            $this->repoPath = $repoPath;
            if ($init) {
                $this->run('init');
            }
            return;
        }
        if (!$createNew) {
            throw new Exception(
                __('"{path}" does not exist', [
                    'path' => $repoPath
                ])
            );
        }
        $parent = realpath(dirname($repoPath));
        if ($parent === false) {
            throw new Exception(
                __('cannot create repository in non-existent directory')
            );
        }
        mkdir($repoPath);
        $this->repoPath = $repoPath;
        if ($init) {
            $this->run('init');
        }
    }

    /**
     * Get the path to the git repo directory (eg. the ".git" directory)
     *
     * @access public
     * @return string
     */
    public function gitDirectoryPath() {
        return ($this->bare) ? $this->repoPath : $this->repoPath.'/.git';
    }

    /**
     * Tests if git is installed
     *
     * @access  public
     * @return  bool
     */
    public function testGit() {
        $status = Process::factory(Git::getBin())->start()->join()->stop();
        return ($status != 127);
    }

    /**
     * Run a command in the git repository
     *
     * Accepts a shell command to run
     *
     * @access  protected
     * @param   string $command to run
     * @return string
     * @throws Exception
     */
    protected function runCommand($command) {

        /* Depending on the value of variables_order, $_ENV may be empty.
         * In that case, we have to explicitly set the new variables with
         * putenv, and call proc_open with env=null to inherit the reset
         * of the system.
         *
         * This is kind of crappy because we cannot easily restore just those
         * variables afterwards.
         *
         * If $_ENV is not empty, then we can just copy it and be done with it.
         */
        if(count($_ENV) === 0) {
            $env = NULL;
            foreach($this->envOpts as $k => $v) {
                putenv(sprintf('%s=%s',$k,$v)); // 临时设置系统环境变量
            }
        } else {
            $env = array_merge($_ENV, $this->envOpts);
        }
        $process = Process::factory($command, [
            'env' => $env,
            'cwd' => $this->repoPath
        ]);

        $status = $process->start()->join()->stop();
        extract($process->getOutput());
        if ($status) {
            throw new Exception($stderr);
        }

        return $stdout;
    }

    /**
     * Run a git command in the git repository
     *
     * Accepts a git command to run
     *
     * @access  public
     * @param   string $command to run
     * @return  string
     */
    public function run($command) {
        return $this->runCommand(Git::getBin().' '.$command);
    }

    /**
     * Runs a 'git status' call
     *
     * Accept a convert to HTML bool
     *
     * @access public
     * @param bool  $html
     * @return string
     */
    public function status($html = false) {
        $msg = $this->run('status');
        if ($html == true) {
            $msg = str_replace("\n", "<br />", $msg);
        }
        return $msg;
    }

    /**
     * Runs a `git add` call
     *
     * Accepts a list of files to add
     *
     * @access  public
     * @param   mixed  $files to add
     * @return  string
     */
    public function add($files = '*') {
        if (is_array($files)) {
            $files = '"'.implode('" "', $files).'"';
        }
        return $this->run("add $files -v");
    }

    /**
     * Runs a `git rm` call
     *
     * Accepts a list of files to remove
     *
     * @access  public
     * @param   mixed $files to remove
     * @param   Boolean $cached use the --cached flag?
     * @return  string
     */
    public function rm($files = '*', $cached = false) {
        if (is_array($files)) {
            $files = '"'.implode('" "', $files).'"';
        }
        return $this->run('rm '.($cached ? '--cached ' : '').$files);
    }


    /**
     * Runs a `git commit` call
     *
     * Accepts a commit message string
     *
     * @access  public
     * @param   string $message commit message
     * @param   boolean $commitAll should all files be committed automatically (-a flag)
     * @return  string
     */
    public function commit($message = '', $commitAll = true) {
        $flags = $commitAll ? '-av' : '-v';
        return $this->run('commit '.$flags.' -m '.escapeshellarg($message));
    }

    /**
     * Runs a `git clone` call to clone the current repository
     * into a different directory
     *
     * Accepts a target directory
     *
     * @access  public
     * @param   string  $target directory
     * @return  string
     */
    public function cloneTo($target) {
        return $this->run('clone --local '.$this->repoPath." $target");
    }

    /**
     * Runs a `git clone` call to clone a different repository
     * into the current repository
     *
     * Accepts a source directory
     *
     * @access  public
     * @param   string  $source directory
     * @return  string
     */
    public function cloneFrom($source) {
        return $this->run("clone --local $source ".$this->repoPath);
    }

    /**
     * Runs a `git clone` call to clone a remote repository
     * into the current repository
     *
     * Accepts a source url
     *
     * @access  public
     * @param   string  $source url
     * @param   string  $reference path
     * @return  string
     */
    public function cloneRemote($source, $reference) {
        return $this->run("clone $reference $source ".$this->repoPath);
    }

    /**
     * Runs a `git clean` call
     *
     * Accepts a remove directories flag
     *
     * @access  public
     * @param   bool  $dirs  delete directories?
     * @param   bool $force   force clean?
     * @return  string
     */
    public function clean($dirs = false, $force = false) {
        return $this->run('clean'.(($force) ? ' -f' : '').(($dirs) ? ' -d' : ''));
    }

    /**
     * Runs a `git branch` call
     *
     * Accepts a name for the branch
     *
     * @access  public
     * @param   string  $branch name
     * @return  string
     */
    public function createBranch($branch) {
        return $this->run("branch $branch");
    }

    /**
     * Runs a `git branch -[d|D]` call
     *
     * Accepts a name for the branch
     *
     * @access  public
     * @param   string  $branch name
     * @param bool $force
     * @return string
     */
    public function deleteBranch($branch, $force = false) {
        return $this->run('branch '.(($force) ? '-D' : '-d')." $branch");
    }

    /**
     * Runs a `git branch` call
     *
     * @access  public
     * @param   bool  $keepAsterisk  keep asterisk mark on active branch
     * @return  array
     */
    public function listBranches($keepAsterisk = false) {
        $branchArray = explode("\n", $this->run('branch'));
        foreach($branchArray as $i => &$branch) {
            $branch = trim($branch);
            if (! $keepAsterisk) {
                $branch = str_replace('* ', '', $branch);
            }
            if ($branch == '') {
                unset($branchArray[$i]);
            }
        }
        return $branchArray;
    }

    /**
     * Lists remote branches (using `git branch -r`).
     *
     * Also strips out the HEAD reference (e.g. "origin/HEAD -> origin/master").
     *
     * @access  public
     * @return  array
     */
    public function listRemoteBranches() {
        $branchArray = explode("\n", $this->run('branch -r'));
        foreach($branchArray as $i => &$branch) {
            $branch = trim($branch);
            if ($branch == '' || strpos($branch, 'HEAD -> ') !== false) {
                unset($branchArray[$i]);
            }
        }
        return $branchArray;
    }

    /**
     * Returns name of active branch
     *
     * @access  public
     * @param   bool $keepAsterisk   keep asterisk mark on branch name
     * @return  string
     */
    public function activeBranch($keepAsterisk = false) {
        $branchArray = $this->listBranches(true);
        $activeBranch = preg_grep('/^\*/', $branchArray);
        reset($activeBranch);
        if ($keepAsterisk) {
            return current($activeBranch);
        }
        return str_replace('* ', '', current($activeBranch));
    }

    /**
     * Runs a `git checkout` call
     *
     * Accepts a name for the branch
     *
     * @access  public
     * @param   string  $branch name
     * @return  string
     */
    public function checkout($branch) {
        return $this->run("checkout $branch");
    }


    /**
     * Runs a `git merge` call
     *
     * Accepts a name for the branch to be merged
     *
     * @access  public
     * @param   string $branch
     * @return  string
     */
    public function merge($branch) {
        return $this->run("merge $branch --no-ff");
    }


    /**
     * Runs a git fetch on the current branch
     *
     * @access  public
     * @return  string
     */
    public function fetch() {
        return $this->run('fetch');
    }

    /**
     * Add a new tag on the current position
     *
     * Accepts the name for the tag and the message
     *
     * @param string $tag
     * @param string $message
     * @return string
     */
    public function addTag($tag, $message = null) {
        if ($message === null) {
            $message = $tag;
        }
        return $this->run("tag -a $tag -m " . escapeshellarg($message));
    }

    /**
     * List all the available repository tags.
     *
     * Optionally, accept a shell wildcard pattern and return only tags matching it.
     *
     * @access	public
     * @param	string	$pattern	Shell wildcard pattern to match tags against.
     * @return	array				Available repository tags.
     */
    public function listTags($pattern = null) {
        $tagArray = explode("\n", $this->run("tag -l $pattern"));
        foreach ($tagArray as $i => &$tag) {
            $tag = trim($tag);
            if ($tag == '') {
                unset($tagArray[$i]);
            }
        }
        return $tagArray;
    }

    /**
     * Push specific branch to a remote
     *
     * Accepts the name of the remote and local branch
     *
     * @param string $remote
     * @param string $branch
     * @return string
     */
    public function push($remote, $branch) {
        return $this->run("push --tags $remote $branch");
    }

    /**
     * Pull specific branch from remote
     *
     * Accepts the name of the remote and local branch
     *
     * @param string $remote
     * @param string $branch
     * @return string
     */
    public function pull($remote, $branch) {
        return $this->run("pull $remote $branch");
    }

    /**
     * List log entries.
     *
     * @param string $format
     * @return string
     */
    public function log($format = null) {
        if ($format === null)
            return $this->run('log');
        else
            return $this->run('log --pretty=format:"' . $format . '"');
    }

    /**
     * Sets the project description.
     *
     * @param string $new
     */
    public function setDescription($new) {
        $path = $this->gitDirectoryPath();
        file_put_contents($path.'/description', $new);
    }

    /**
     * Gets the project description.
     *
     * @return string
     */
    public function getDescription() {
        $path = $this->gitDirectoryPath();
        return file_get_contents($path.'/description');
    }

    /**
     * Sets custom environment options for calling Git
     *
     * @param string $key
     * @param string $value
     */
    public function setEnv($key, $value) {
        $this->envOpts[$key] = $value;
    }
}