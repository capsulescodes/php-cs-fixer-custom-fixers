<?php

use CapsulesCodes\PhpCsFixerCustomFixers\Fixers\NoSpaceAfterKeywordFixer;


it( 'removes spaces between keywords and opening parenthesis', function() : void
{
	$fix = $this->fix( 'tests/stubs/before/no-space-after-keyword.php', NoSpaceAfterKeywordFixer::class );

	expect( $fix )->toEqual( file_get_contents( 'tests/stubs/after/no-space-after-keyword/default.php' ) );
} );
