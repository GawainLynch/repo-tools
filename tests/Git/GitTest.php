<?php

namespace Bolt\RepoTools\Tests;

use Bolt\RepoTools\Git\Commit;
use Bolt\RepoTools\Git\Git;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \Bolt\RepoTools\Git\Git
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class GitTest extends TestCase
{
    /** @var string */
    private $root;

    public function setUp()
    {
        $tmp = __DIR__ . '/../tmp';

        $fs = new Filesystem();
        $fs->mkdir($tmp);
        $zip = new \ZipArchive;
        $zip->open(__DIR__ . '/../resources/test-repo.zip') ;
        $zip->extractTo($tmp . '/repo/');
        $zip->close();

        $this->root = realpath($tmp . '/repo/');
    }

    protected function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__ . '/../tmp');
    }

    public function testConstructor()
    {
        $git = new Git($this->root);

        $this->assertObjectHasAttribute('repositoryPath', $git);
        $this->assertObjectHasAttribute('gitPath', $git);
    }

    /**
     * @expectedException \Bolt\RepoTools\Exception\GitException
     * @expectedExceptionMessageRegExp /Given repository path does not exist/
     */
    public function testConstructorInvalidPath()
    {
        new Git($this->root . '/not-here');
    }

    public function testCreate()
    {
        $git = Git::create($this->root);

        $this->assertInstanceOf(Git::class, $git);
        $this->assertObjectHasAttribute('repositoryPath', $git);
        $this->assertObjectHasAttribute('gitPath', $git);
    }

    public function testIsClean()
    {
        $git = new Git($this->root);
        $this->assertTrue($git->isClean());
    }

    public function testIsDirty()
    {
        $git = new Git($this->root);

        touch($this->root . '/Test.php');
        $this->assertFalse($git->isClean());
    }

    public function testCommit()
    {
        $git = new Git($this->root);

        touch($this->root . '/Test.php');
        $this->assertFalse($git->isClean());

        $git->add($this->root . '/Test.php');
        $commit = $git->commit($this->root . '/Test.php', 'Added PHP file.');

        $this->assertTrue($git->isClean());
        $this->assertInstanceOf(Commit::class, $commit);
        $this->assertSame('Added PHP file.', $commit->getMessage());
    }

    /**
     * @expectedException \Bolt\RepoTools\Exception\GitException
     * #@expectedExceptionMessage Commit message can not be empty.
     */
    public function testCommitEmptyMessage()
    {
        $git = new Git($this->root);
        $git->commit($this->root . '/Test.php', '');
    }

    public function testCommits()
    {
        $git = new Git($this->root);
        $commits = $git->commits();

        $this->assertCount(4, $commits);
        foreach ($commits as $commit) {
            $this->assertInstanceOf(Commit::class, $commit);
        }
    }

    public function testCommitsReverse()
    {
        $git = new Git($this->root);

        $commits = $git->commits(null, false);
        $first = $commits[0];
        $last = $commits[3];

        $this->assertSame('b2ff6bfebadb8f310f17cafd9a7817172d4ff608', $first->getSha1());
        $this->assertSame('4ac611908f84dda0dddcd7a55bc781118e5fa70e', $last->getSha1());

        $commits = $git->commits(null, true);
        $first = $commits[0];
        $last = $commits[3];

        $this->assertSame('4ac611908f84dda0dddcd7a55bc781118e5fa70e', $first->getSha1());
        $this->assertSame('b2ff6bfebadb8f310f17cafd9a7817172d4ff608', $last->getSha1());
    }

    public function testCommitsLimit()
    {
        $git = new Git($this->root);
        $commits = $git->commits(2);

        $this->assertCount(2, $commits);
    }

    public function testBranchChange()
    {
        $git = new Git($this->root);

        $this->assertSame('master', $git->currentBranch());

        $git->checkout('development');
        $this->assertSame('development', $git->currentBranch());
    }

    /**
     * @expectedException \Bolt\RepoTools\Exception\GitException
     * @expectedExceptionMessage error: pathspec 'koala' did not match any file(s) known to git.
     */
    public function testBranchChangeInvalid()
    {
        $git = new Git($this->root);
        $git->checkout('koala');
    }

    public function testDiffCommits()
    {
        $expected = <<<EOF
diff --git a/README.md b/README.md
index caff355..4b04a2e 100644
--- a/README.md
+++ b/README.md
@@ -1,2 +1,6 @@
 My Project
 ==========
+
+## Section 1
+
+This is something.

EOF;
        $git = new Git($this->root);

        $diff = $git->diff('1190bd5f00b4d65d248493a57f6123abab9bcde1', '4853e3ad49e438534b521944d64246e3585b8dd5');

        $this->assertSame($expected, $diff);
    }

    public function testDiffBranch()
    {
        $expected = <<<EOF
diff --git a/Development.php b/Development.php
new file mode 100644
index 0000000..4335523
--- /dev/null
+++ b/Development.php
@@ -0,0 +1,3 @@
+<?php
+
+echo "Under construction";

EOF;
        $git = new Git($this->root);

        $diff = $git->diff('master', 'development');

        $this->assertSame($expected, $diff);
    }

    public function testRemote()
    {
        $git = new Git($this->root);
        $remotes = $git->remote();

        $this->assertSame('origin', $remotes);
    }

    public function testRemoteVerbose()
    {
        $expected = <<<EOF
origin\thttps:://example.com (fetch)
origin\thttps:://example.com (push)
EOF;
        $git = new Git($this->root);

        $remotes = $git->remote(null, ['-v']);

        $this->assertSame($expected, $remotes);
    }

    public function testRemoteAdd()
    {
        $expected = <<<EOF
origin\thttps:://example.com (fetch)
origin\thttps:://example.com (push)
upstream\thttps://github.com/bolt/repo-tools.git (fetch)
upstream\thttps://github.com/bolt/repo-tools.git (push)
EOF;
        $git = new Git($this->root);

        $git->remote('add', ['upstream', 'https://github.com/bolt/repo-tools.git']);
        $remotes = $git->remote(null, ['-v']);

        $this->assertSame($expected, $remotes);
    }

    public function testPullRemote()
    {
        $git = new Git($this->root);
        $expected = <<<EOF
Updating b2ff6bf..bf9ce57
Fast-forward
 Development.php | 3 +++
 1 file changed, 3 insertions(+)
 create mode 100644 Development.php

EOF;
        $fs = new Filesystem();
        $fs->mirror($this->root, $this->root . '/../upstream/');

        $git->remote('add', ['upstream', $this->root . '/../upstream/']);
        $pull = $git->pull('upstream', 'development');

        $this->assertSame($expected, $pull);
    }

    public function testInitNew()
    {
        $git = new Git($this->root);
        $fs = new Filesystem();
        $fs->remove($this->root . '/.git');

        $init = $git->init();

        $this->assertRegExp('/Initialized empty Git repository in/', $init);
    }

    public function testInitExiting()
    {
        $git = new Git($this->root);

        $init = $git->init();

        $this->assertRegExp('/Reinitialized existing Git repository in/', $init);
    }

    //public function test()
    //{
    //}

    //public function test()
    //{
    //}
}
