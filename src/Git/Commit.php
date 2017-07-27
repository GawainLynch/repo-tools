<?php

namespace Bolt\RepoTools\Git;

use Carbon\Carbon;

/**
 * A single Git commit.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Commit
{
    /** @var string */
    protected $sha1;
    /** @var string */
    protected $author;
    /** @var Carbon */
    protected $date;
    /** @var string */
    protected $message;

    /**
     * Constructor.
     *
     * @param string $sha1
     * @param string $author
     * @param Carbon $date
     * @param string $message
     */
    public function __construct($sha1, $author, Carbon $date, $message)
    {
        $this->sha1 = $sha1;
        $this->author = $author;
        $this->date = $date;
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getSha1()
    {
        return $this->sha1;
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return Carbon
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    public function __toString()
    {
        return sprintf(
            "commit %s\nAuthor: %s\nDate:   %s\n\n    %s\n",
            $this->sha1,
            $this->author,
            $this->date->toRfc822String(),
            $this->message
        );
    }
}
