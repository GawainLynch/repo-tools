<?php

namespace Bolt\RepoTools\Git;

use Bolt\RepoTools\Exception\GitException;
use Carbon\Carbon;
use Symfony\Component\Process\Process;

/**
 * Git helper.
 *
 * Based on work by Sebastian Bergmann <sebastian@phpunit.de>
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Git
{
    /** @var string */
    protected $repositoryPath;
    /** @var string */
    protected $gitPath;

    /**
     * Constructor.
     *
     * @param string $repositoryPath
     * @param string $gitPath
     */
    public function __construct($repositoryPath, $gitPath = 'git')
    {
        if (!file_exists($repositoryPath . '/.git')) {
            throw new GitException(sprintf('Given repository path does not exist: %s', $repositoryPath));
        }

        $this->repositoryPath = $repositoryPath;
        $this->gitPath = $gitPath;
    }

    /**
     * @param string $repositoryPath
     * @param string $gitBinary
     *
     * @return Git
     */
    public static function create($repositoryPath, $gitBinary = 'git')
    {
        return new static($repositoryPath, $gitBinary);
    }

    /**
     * Add a file to the git tracker.
     *
     * @param string|array $target
     *
     * @return string
     */
    public function add($target)
    {
        return $this->run(sprintf('add %s', implode(' ', (array) $target)));
    }

    /**
     * @param string $revision
     *
     * @return string
     */
    public function checkout($revision)
    {
        return $this->run(sprintf('checkout --force --quiet %s', $revision));
    }

    /**
     * Commit file(s) to the git repository.
     *
     * @param string|array $target
     * @param string       $message
     *
     * @return Commit
     */
    public function commit($target, $message)
    {
        if (trim($message) === '') {
            throw new GitException('Commit message can not be empty.');
        }

        $this->run(sprintf('commit %s -m "%s"', implode(' ', (array) $target), $message));

        return $this->commits(1)[0];
    }

    /**
     * Return repository commits.
     *
     * @param int|null $limit
     * @param bool     $reverse
     *
     * @return Commit[]
     */
    public function commits($limit = null, $reverse = false)
    {
        $command = sprintf(
            'log --no-merges --date-order --format=medium %s %s',
            $limit ? '--max-count=' . $limit : '',
            $reverse ? '--reverse' : ''
        );
        $output = $this->run($command);
        $output = explode(PHP_EOL, $output);

        $numLines = count($output);
        $revisions = [];

        for ($i = 0; $i < $numLines; ++$i) {
            $parts = explode(' ', $output[$i]);
            $firstLine = $parts[0];

            if ($firstLine === 'commit') {
                $sha1 = $parts[1];
            } elseif ($firstLine === 'Author:') {
                $author = implode(' ', array_slice($parts, 1));
            } elseif ($firstLine === 'Date:' && isset($author) && isset($sha1)) {
                $date = implode(' ', array_slice($parts, 3));
                $date = Carbon::createFromFormat('D M j H:i:s Y O', $date);
                $message = isset($output[$i + 2]) ? trim($output[$i + 2]) : '';

                $revisions[] = new Commit($sha1, $author, $date, $message);

                unset($author);
                unset($sha1);
            }
        }

        return $revisions;
    }

    /**
     * Return the name of the currently checked-out branch.
     *
     * @return string
     */
    public function currentBranch()
    {
        return trim($this->run('symbolic-ref --short -q HEAD'));
    }

    /**
     * Print the diff between two branches or commits.
     *
     * @param string $from
     * @param string $to
     *
     * @return string
     */
    public function diff($from, $to)
    {
        return $this->run(sprintf('diff --no-ext-diff %s %s', $from, $to));
    }

    /**
     * Initialise a git repository.
     *
     * @return string
     */
    public function init()
    {
        return $this->run('init');
    }

    /**
     * Check if the current working copy has any uncommitted changes.
     *
     * @return bool
     */
    public function isClean()
    {
        $output = $this->run('status -s');

        return $output === '';
    }

    /**
     * Pull a branch from a remote.
     *
     * @param string $remote
     * @param string $branch
     *
     * @return string
     */
    public function pull($remote = 'origin', $branch = 'master', $rebase = false)
    {
        return $this->run(sprintf('pull %s %s %s', $rebase ? '--rebase' : '', $remote, $branch));
    }

    /**
     * Perform an action on remote(s).
     *
     * @param string|null $action
     * @param array|null  $options
     *
     * @return string
     */
    public function remote($action = null, $options = null)
    {
        return trim($this->run(sprintf('remote %s %s', $action, implode(' ', (array) $options))));
    }

    /**
     * @param string $command
     *
     * @throws GitException
     *
     * @return string
     */
    protected function run($command)
    {
        $command = sprintf('LC_ALL=en_US.UTF-8 %s -C %s %s', $this->gitPath, escapeshellarg($this->repositoryPath), $command);
        $process = new Process($command);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new GitException($process->getErrorOutput());
        }

        return $process->getOutput();
    }
}
