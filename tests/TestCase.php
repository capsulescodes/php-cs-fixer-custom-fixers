<?php

namespace CapsulesCodes\Fixers\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use PhpCsFixer\Finder;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\RuleSet\RuleSet;
use PhpCsFixer\Config;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;


abstract class TestCase extends BaseTestCase
{
    public Finder $finder;

    protected function setUp() : void
    {
        $this->finder = Finder::create()->in( __DIR__ . '/stubs/before' )->name( '*.php' );
    }

    public function fix( string $path, string $class, array $rules = [] ) : string
    {
        $this->finder->filter( fn( SplFileInfo $name ) : bool => str_contains( $name->getPathname(), $path ) );

        $factory = ( new FixerFactory() )->registerCustomFixers( [ new $class() ] );

        if( ! empty( $rules ) ) $factory->useRuleSet( new Ruleset( $rules ) );

        $fixed = clone Tokens::fromCode( file_get_contents( $path ) );

        foreach( $this->finder as $file )
        {
            foreach( $factory->getFixers() as $fixer )
            {
                if( $fixer->isCandidate( $fixed ) && $fixer->supports( $file ) && ! $fixer->isRisky() ) $fixer->fix( $file, $fixed );
            }
        }

        return $fixed->generateCode();
    }
}
