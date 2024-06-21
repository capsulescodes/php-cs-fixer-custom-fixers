<?php declare( strict_types = 1 );


namespace CapsulesCodes\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use SplFileInfo;
use InvalidArgumentException;


final class MultipleLinesAfterImportsFixer extends AbstractFixer implements ConfigurableFixerInterface, WhitespacesAwareFixerInterface
{
    use ConfigurableFixerTrait;


    public function getName() : string
    {
        return 'CapsulesCodes/multiple_lines_after_imports';
    }

    public function getDefinition() : FixerDefinitionInterface
    {
        return new FixerDefinition( 'Each namespace use MUST go on its own line and there MUST be two blank lines after the use statements block.', [] );
    }

    public function getPriority() : int
    {
        return -11;
    }

    protected function createConfigurationDefinition() : FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver( [

            ( new FixerOptionBuilder( 'lines', 'Set {number} blank lines after the use statements block' ) )->setAllowedTypes( [ 'integer' ] )->setDefault( 2 )->getOption()

        ] );
    }

    public function isCandidate( Tokens $tokens ) : bool
    {
        return $tokens->isTokenKindFound( T_USE );
    }

    protected function applyFix( SplFileInfo $file, Tokens $tokens ) : void
    {
        $ending = $this->whitespacesConfig->getLineEnding();

        $tokensAnalyzer = new TokensAnalyzer( $tokens );

        $added = 0;

        foreach( $tokensAnalyzer->getImportUseIndexes() as $index )
        {
            $index += $added;

            $indent = '';

            if( $tokens[ $index - 1 ]->isWhitespace( " \t" ) && $tokens[ $index - 2 ]->isGivenKind( T_COMMENT ) )
            {
                $indent = $tokens[ $index - 1 ]->getContent();
            }
            elseif( $tokens[ $index - 1 ]->isWhitespace() )
            {
                $indent = $this::calculateTrailingWhitespaceIndent( $tokens[ $index - 1 ] );
            }

            $semicolonIndex = $tokens->getNextTokenOfKind( $index, [ ';', [ T_CLOSE_TAG ] ] );

            $insertIndex = $semicolonIndex;

            if( $tokens[ $semicolonIndex ]->isGivenKind( T_CLOSE_TAG ) )
            {
                if( $tokens[ $insertIndex - 1 ]->isWhitespace() ) --$insertIndex;

                $tokens->insertAt( $insertIndex, new Token( ';' ) );

                ++$added;
            }

            if( $semicolonIndex === count( $tokens ) - 1 )
            {
                $tokens->insertAt( $insertIndex + 1, new Token( [ T_WHITESPACE, str_repeat( $ending, $this->configuration[ 'lines' ] ).$indent ] ) );

                ++$added;
            }
            else
            {
                $newline = str_repeat( $ending, $this->configuration[ 'lines' ] );

                $tokens[ $semicolonIndex ]->isGivenKind( T_CLOSE_TAG ) ? --$insertIndex : ++$insertIndex;

                if( $tokens[ $insertIndex ]->isWhitespace( " \t" ) && $tokens[ $insertIndex + 1 ]->isComment() ) ++$insertIndex;

                if( $tokens[ $insertIndex ]->isComment() ) ++$insertIndex;

                $afterSemicolon = $tokens->getNextMeaningfulToken( $semicolonIndex );

                if( null === $afterSemicolon || !$tokens[ $afterSemicolon ]->isGivenKind( T_USE ) )  $newline .= $ending;

                if( $tokens[ $insertIndex ]->isWhitespace() )
                {
                    $nextToken = $tokens[ $insertIndex ];

                    if( 3 === substr_count( $nextToken->getContent(), "\n" ) )  continue;

                    $nextMeaningfulAfterUseIndex = $tokens->getNextMeaningfulToken( $insertIndex );

                    if( null !== $nextMeaningfulAfterUseIndex && $tokens[ $nextMeaningfulAfterUseIndex ]->isGivenKind( T_USE ) )
                    {
                        if( substr_count( $nextToken->getContent(), "\n" ) < 1 ) $tokens[$insertIndex] = new Token( [ T_WHITESPACE, $newline.$indent.ltrim( $nextToken->getContent() ) ] );
                    }
                    else
                    {
                        $tokens[ $insertIndex ] = new Token( [ T_WHITESPACE, $newline.$indent.ltrim( $nextToken->getContent() ) ] );
                    }
                }
                else
                {
                    $tokens->insertAt( $insertIndex, new Token( [ T_WHITESPACE, $newline.$indent ] ) );

                    ++$added;
                }
            }
        }
    }

    public static function calculateTrailingWhitespaceIndent( Token $token ) : string
    {
        if( ! $token->isWhitespace() ) throw new InvalidArgumentException( sprintf( 'The given token must be whitespace, got "%s".', $token->getName() ) );

        $str = strrchr( str_replace( [ "\r\n", "\r" ], "\n", $token->getContent() ), "\n" );

        if( false === $str ) return '';

        return ltrim( $str, "\n" );
    }
}
