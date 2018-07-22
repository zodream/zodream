<?php
namespace Zodream\Domain\Git;
/*
 * Git.php
 *
 * A PHP git library
 *
 * @package    Git.php
 * @version    0.1.4
 * @author     James Brumond
 * @copyright  Copyright 2013 James Brumond
 * @repo       http://github.com/kbjr/Git.php
 */
class Git {

    /**
     * Git executable location
     *
     * @var string
     */
    protected static $bin = '/usr/bin/git';

    /**
     * Sets git executable path
     *
     * @param string $path executable location
     */
    public static function setBin($path) {
        static::$bin = $path;
    }

    /**
     * Gets git executable path
     */
    public static function getBin() {
        return static::$bin;
    }

    /**
     * Sets up library for use in a default Windows environment
     */
    public static function windowsMode() {
        static::setBin('git');
    }

    /**
     * Create a new git repository
     *
     * Accepts a creation path, and, optionally, a source path
     *
     * @access  public
     * @param   string $repoPath repository path
     * @param   string $source directory to source
     * @return  GitRepo
     */
    public static function &create($repoPath, $source = null) {
        return GitRepo::createNew($repoPath, $source);
    }

    /**
     * Open an existing git repository
     *
     * Accepts a repository path
     *
     * @access  public
     * @param   string $repoPath repository path
     * @return  GitRepo
     */
    public static function open($repoPath) {
        return new GitRepo($repoPath);
    }

    /**
     * Clones a remote repo into a directory and then returns a GitRepo object
     * for the newly created local repo
     *
     * Accepts a creation path and a remote to clone from
     *
     * @access  public
     * @param   string $repoPath repository path
     * @param   string $remote remote source
     * @param   string $reference reference path
     * @return  GitRepo
     **/
    public static function &cloneRemote($repoPath, $remote, $reference = null) {
        return GitRepo::createNew($repoPath, $remote, true, $reference);
    }

    /**
     * Checks if a variable is an instance of GitRepo
     *
     * Accepts a variable
     *
     * @access  public
     * @param   mixed $var variable
     * @return  bool
     */
    public static function isRepo($var) {
        return $var instanceof GitRepo;
    }
}