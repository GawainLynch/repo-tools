<?php

namespace Bolt\RepoTools\Tests;

use Bolt\RepoTools\Git\Commit;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Bolt\RepoTools\Git\Commit
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class CommitTest extends TestCase
{
    public function testConstructor()
    {
        $date = Carbon::now();
        $commit = new Commit(
            'deadbeef',
            'Gawain Lynch <gawain.lynch@gmail.com>',
            $date,
            'Initial commit'
        );

        $this->assertObjectHasAttribute('sha1', $commit);
        $this->assertObjectHasAttribute('author', $commit);
        $this->assertObjectHasAttribute('date', $commit);
        $this->assertObjectHasAttribute('message', $commit);
    }

    public function providerGetters()
    {
        $sha1 = 'deadbeef';
        $author = 'Gawain Lynch <gawain.lynch@gmail.com>';
        $date = Carbon::now();
        $message = 'Initial commit';

        return [
            [$sha1, $author, $date, $message, $sha1, 'getSha1'],
            [$sha1, $author, $date, $message, $author, 'getAuthor'],
            [$sha1, $author, $date, $message, $date, 'getDate'],
            [$sha1, $author, $date, $message, $message, 'getMessage'],
        ];
    }

    /**
     * @dataProvider providerGetters
     */
    public function testGetters($sha1, $author, $date, $message, $expected, $method)
    {
        $commit = new Commit($sha1, $author, $date, $message);

        $this->assertSame($expected, call_user_func([$commit, $method]));
    }


    public function testToString()
    {
        $date = Carbon::now();
        $commit = new Commit(
            'deadbeef',
            'Gawain Lynch <gawain.lynch@gmail.com>',
            $date,
            'Initial commit'
        );
        $expected = <<<EOF
commit deadbeef
Author: Gawain Lynch <gawain.lynch@gmail.com>
Date:   {$date->toRfc822String()}

    Initial commit

EOF;


        $this->assertSame($expected, (string) $commit);
    }
}
