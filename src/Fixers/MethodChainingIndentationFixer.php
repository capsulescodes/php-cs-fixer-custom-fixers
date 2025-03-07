<?php declare( strict_types = 1 );


namespace CapsulesCodes\PhpCsFixerCustomFixers\Fixers;

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
use PhpCsFixer\Tokenizer\Token;
use SplFileInfo;
use PhpCsFixer\Preg;


final class MethodChainingIndentationFixer extends AbstractFixer implements ConfigurableFixerInterface, WhitespacesAwareFixerInterface
{
    use ConfigurableFixerTrait;


    public function getName() : string
    {
        return 'CapsulesCodes/method_chaining_indentation';
    }

    public function getDefinition() : FixerDefinitionInterface
    {
        return new FixerDefinition( 'Method chaining MUST be properly indented.', [] );
    }

    public function getPriority() : int
    {
        return 0;
    }

    protected function createConfigurationDefinition() : FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver( [

            ( new FixerOptionBuilder( 'single-line', 'Set chains on single line' ) )->setAllowedTypes( [ 'bool' ] )->setDefault( false )->getOption(),
            ( new FixerOptionBuilder( 'multi-line', 'Set chains on next line if {number} chains' ) )->setAllowedTypes( [ 'integer' ] )->setDefault( 4 )->getOption()

        ] );
    }

    public function isCandidate( Tokens $tokens ) : bool
    {
        return $tokens->isAnyTokenKindsFound(  Token::getObjectOperatorKinds()  );
    }

    protected function applyFix( SplFileInfo $file, Tokens $tokens ) : void
    {
        for( $index = 1, $count = count( $tokens ); $index < $count; ++$index )
        {
            if( $tokens[ $index ]->isGivenKind( T_DOUBLE_COLON ) )
            {
                if( $tokens[ $index - 1 ]->isWhitespace() ) $tokens->clearAt( $index - 1 );

                if( $tokens[ $index + 1 ]->isWhitespace() ) $tokens->clearAt( $index + 1 );
            }

            if( ! $tokens[ $index ]->isObjectOperator() ) continue;

            $chainings = [];

            $parenthesis = 0;

            for( $j = $index; $j < $count; ++$j )
            {
                if( $tokens[ $j ]->getContent() === ';' ) break;

                if( $tokens[ $j ]->getContent() === '(' ) $parenthesis++;

                if( $tokens[ $j ]->getContent() === ')' ) $parenthesis--;

                if( $parenthesis == 0 && $tokens[ $j ]->isObjectOperator() && $tokens[ $tokens->getPrevMeaningfulToken( $j ) ]->getContent() === ')' ) $chainings[] = $j;
            }


            if( $this->configuration[ 'single-line' ] )
            {
                for( $k = count( $chainings ) - 1; $k >= 0; --$k )
                {
                    $chaining = $chainings[ $k ];

                    if( $tokens[ $chaining - 1 ]->isWhitespace() ) $tokens->clearAt( $chaining - 1 );
                }

                continue;
            }


            if( array_key_exists( 'multi-line', $this->configuration ) )
            {
                for( $k = count( $chainings ) - 1; $k >= 0; --$k )
                {
                    $chaining = $chainings[ $k ];

                    if( count( $chainings ) >= $this->configuration[ 'multi-line' ] )
                    {
                        if( $tokens[ $chaining - 1 ]->isWhitespace() ) $tokens->clearAt( $chaining - 1 );

                        $expectedIndent = $this->getExpectedIndentAt( $tokens, $chainings[ 0 ] );

                        $expectedPosition = $this->getExpectedPositionAt( $tokens, $chainings[ 0 ] );

                        $expected = strlen( $expectedPosition ) ? $expectedPosition : $expectedIndent;

                        $line = new Token( [ T_WHITESPACE, $this->whitespacesConfig->getLineEnding() . $expected ] );

                        $tokens->insertAt( $chaining, $line );
                    }
                }

                $count = count( $tokens );

                ++$index;
            }
        }
    }

    private function getExpectedPositionAt( Tokens $tokens, int $index ) : string
    {
        $index = $tokens->getPrevMeaningfulToken( $index );

        $position = 0;

        $found = false;

        for( $l = $index; $l >= 0; --$l )
        {
            if( str_contains( $tokens[ $l ]->getContent(), "\n" ) )
            {
                $array = explode( "\n", $tokens[ $l ]->getContent() );

                $position += strlen( end( $array ) );

                break;
            }

            if( $found ) $position += strlen( $tokens[ $l ]->getContent() );

            if( $tokens[ $l ]->isGivenKind( [ ...Token::getObjectOperatorKinds(), T_DOUBLE_COLON ] ) ) $found = true;
        }

        return str_repeat( " ", $position );
    }

    private function getExpectedIndentAt( Tokens $tokens, int $index ) : string
    {
        $index = $tokens->getPrevMeaningfulToken( $index );

        $indent = $this->whitespacesConfig->getIndent();

        for( $l = $index; $l >= 0; --$l )
        {
            $currentIndent = $this->getIndentAt( $tokens, $l );

            if( $currentIndent === null ) continue;

            if( $this->currentLineRequiresExtraIndentLevel( $tokens, $l, $index ) ) return "{$currentIndent}{$indent}";

            return $currentIndent;
        }

        return $indent;
    }

    private function getIndentAt( Tokens $tokens, int $index ) : string | null
    {
        if( Preg::match( '/\R{1}(\h*)$/', $this->getIndentContentAt( $tokens, $index ), $matches ) ) return $matches[ 1 ];

        return null;
    }

    private function getIndentContentAt( Tokens $tokens, int $index ) : string
    {
        if( !$tokens[ $index ]->isGivenKind( [ T_WHITESPACE, T_INLINE_HTML ] ) ) return '';

        $content = $tokens[ $index ]->getContent();

        if( $tokens[ $index ]->isWhitespace() && $tokens[ $index - 1 ]->isGivenKind( T_OPEN_TAG ) ) $content = $tokens[ $index - 1 ]->getContent().$content;

        if( Preg::match( '/\R/', $content ) ) return $content;

        return '';
    }

    private function currentLineRequiresExtraIndentLevel( Tokens $tokens, int $start, int $end ) : bool
    {
        $firstMeaningful = $tokens->getNextMeaningfulToken( $start );

        if($tokens[ $firstMeaningful ]->isObjectOperator() )
        {
            $thirdMeaningful = $tokens->getNextMeaningfulToken( $tokens->getNextMeaningfulToken( $firstMeaningful ) );

            return $tokens[ $thirdMeaningful ]->equals( '(' ) && $tokens->findBlockEnd( Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $thirdMeaningful ) > $end;
        }

        return ! $tokens[ $end ]->equals( ')' ) || $tokens->findBlockStart( Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $end ) >= $start;
    }
}
