<?php

use CapsulesCodes\PhpCsFixerCustomFixers\Fixers\MethodChainingIndentationFixer;


it( "indents method chainings", function() : void
{
    $fix = $this->fix( 'tests/stubs/before/method-chaining-indentation.php', MethodChainingIndentationFixer::class );

    expect( $fix )->toEqual( file_get_contents( 'tests/stubs/after/method-chaining-indentation/default.php' ) );
} );


it( "indents method chainings on zero multiple lines threshold", function() : void
{
    $rules = [ 'CapsulesCodes/method_chaining_indentation' => [ 'multi-line' => 0 ] ];

    $fix = $this->fix( 'tests/stubs/before/method-chaining-indentation.php', MethodChainingIndentationFixer::class, $rules );

    expect( $fix )->toEqual( file_get_contents( 'tests/stubs/after/method-chaining-indentation/multi-line-zero.php' ) );
} );


it( "indents method chainings on eight multiple lines threshold", function() : void
{
    $rules = [ 'CapsulesCodes/method_chaining_indentation' => [ 'multi-line' => 8 ] ];

    $fix = $this->fix( 'tests/stubs/before/method-chaining-indentation.php', MethodChainingIndentationFixer::class, $rules );

    expect( $fix )->toEqual( file_get_contents( 'tests/stubs/after/method-chaining-indentation/multi-line-eight.php' ) );
} );


it( "indents method chainings on a single line", function() : void
{
    $rules = [ 'CapsulesCodes/method_chaining_indentation' => [ 'single-line' => true ] ];

    $fix = $this->fix( 'tests/stubs/before/method-chaining-indentation.php', MethodChainingIndentationFixer::class, $rules );

    expect( $fix )->toEqual( file_get_contents( 'tests/stubs/after/method-chaining-indentation/single-line.php' ) );
} );
