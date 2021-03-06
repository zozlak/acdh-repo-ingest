<?php

/*
 * The MIT License
 *
 * Copyright 2019 Austrian Centre for Digital Humanities.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace acdhOeaw\arche\lib\ingest\tests;

use DateTime;
use acdhOeaw\arche\lib\Repo;
use acdhOeaw\arche\lib\exception\Deleted;
use acdhOeaw\arche\lib\exception\NotFound;

/**
 * Description of HelpersTrait
 *
 * @author zozlak
 */
abstract class TestBase extends \PHPUnit\Framework\TestCase {

    static protected Repo $repo;
    static protected object $config;
    static private int $n = 1;

    static public function setUpBeforeClass(): void {
        $cfgFile      = __DIR__ . '/config.yaml';
        self::$config = json_decode(json_encode(yaml_parse_file($cfgFile)));
        self::$repo   = Repo::factory($cfgFile);
    }

    static public function tearDownAfterClass(): void {
        
    }

    /**
     * 
     * @var array<RepoResource>
     */
    private array $resources;

    private float $time = 0;
    
    public function setUp(): void {
        $this->resources = [];
        $this->startTimer();
    }

    public function tearDown(): void {
        $this->noteTime('test ' . self::$n++);
        self::$repo->rollback();

        // delete resources starting with the "most metadata rich" which is a simple heuristic for avoiding 
        // unneeded resource updates when deleting one pointed by many others (such resources are typicaly 
        // "metadata poor" therefore deleting them as the last ones should do the job)
        $queue = [];
        foreach ($this->resources as $n => $i) {
            try {
                $queue[$n] = count($i->getGraph()->propertyUris());
            } catch (Deleted $e) {
                
            } catch (NotFound $e) {
                
            }
        }
        arsort($queue);
        self::$repo->begin();
        foreach ($queue as $n => $count) {
            try {
                $this->resources[$n]->delete(true, true);
            } catch (Deleted $e) {
                
            } catch (NotFound $e) {
                
            }
        }
        self::$repo->commit();
        if (is_dir(__DIR__ . '/tmp')) {
            system('rm -fR ' . __DIR__ . '/tmp');
        }
    }

    protected function noteResources(array $res): void {
        $this->resources = array_merge($this->resources, array_values($res));
    }

    protected function startTimer(): void {
        $this->time = microtime(true);
    }

    protected function noteTime(string $msg = ''): void {
        $t = microtime(true) - $this->time;
        file_put_contents(__DIR__ . '/time.log', (new DateTime())->format('Y-m-d H:i:s.u') . "\t$t\t$msg\n", \FILE_APPEND);
    }
}
