<?php

declare( strict_types = 1 );


namespace CapsulesCodes\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;


final class SpacesInsideSquareBracesFixer extends AbstractFixer implements ConfigurableFixerInterface
{
    public function getName() : string
    {
        return 'CapsulesCodes/spaces_inside_square_braces';
    }

    public function getDefinition() : FixerDefinitionInterface
    {
        return new FixerDefinition( 'Brackets must be declared using the configured whitespace.', [] );
    }

    public function getPriority() : int
    {
        return 3;
    }

    protected function createConfigurationDefinition() : FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver( [

            ( new FixerOptionBuilder( 'space', 'Whether to have `single` or `none` space inside parentheses.' ) )
                ->setAllowedValues( [ 'none', 'single' ] )
                ->setDefault( 'none' )
                ->getOption()

        ] );
    }

    public function isCandidate( Tokens $tokens ) : bool
    {
        return $tokens->isAnyTokenKindsFound( [ '[', CT::T_ARRAY_SQUARE_BRACE_OPEN ] );
    }

    protected function applyFix( SplFileInfo $file, Tokens $tokens ) : void
    {
        if( $this->configuration[ 'space' ] === 'none' )
        {
            foreach( $tokens as $index => $token )
            {
                if( ! $token->equalsAny( [ '[', [ CT::T_ARRAY_SQUARE_BRACE_OPEN ] ] ) ) continue;

                $prevIndex = $tokens->getPrevMeaningfulToken( $index );

                if( $prevIndex !== null && $tokens[ $prevIndex ]->isGivenKind( T_ARRAY ) ) continue;

                $blockType = Tokens::detectBlockType( $tokens[ $index ] );

                $endIndex = $tokens->findBlockEnd( ( int ) $blockType['type'], $index );

                if( ! $tokens[ $tokens->getNextNonWhitespace( $index ) ]->isComment() ) $this->removeSpaceAroundToken( $tokens, $index + 1 );

                if( ! $tokens[ $tokens->getPrevMeaningfulToken( $endIndex ) ]->equals( ',' ) ) $this->removeSpaceAroundToken( $tokens, $endIndex - 1 );
            }
        }

        if ( $this->configuration[ 'space' ] === 'single' )
        {
            foreach( $tokens as $index => $token )
            {
                if( ! $token->equalsAny( ['[', [ CT::T_ARRAY_SQUARE_BRACE_OPEN ] ] ) ) continue;

                $blockType = Tokens::detectBlockType( $tokens[ $index ] );

                $endParenthesisIndex = $tokens->findBlockEnd( ( int ) $blockType[ 'type' ], $index );

                $blockContent = $this->getBlockContent( $index, $endParenthesisIndex, $tokens );

                if( count( $blockContent ) === 1 && in_array( ' ', $blockContent, true ) )
                {
                    $this->removeSpaceAroundToken( $tokens, $index + 1 );

                    continue;
                }

                $nextMeaningfulTokenIndex = $tokens->getNextMeaningfulToken( $index );

                if( $tokens[$nextMeaningfulTokenIndex]->getContent() === ']' ) continue;

                $afterParenthesisIndex = $tokens->getNextNonWhitespace( $endParenthesisIndex );

                $afterParenthesisToken = $tokens[ $afterParenthesisIndex ];

                if( $afterParenthesisToken->isGivenKind( CT::T_USE_LAMBDA ) )
                {
                    $useStartParenthesisIndex = $tokens->getNextTokenOfKind( $afterParenthesisIndex, [ '[' ] );

                    $useEndParenthesisIndex = $tokens->findBlockEnd( Tokens::BLOCK_TYPE_INDEX_SQUARE_BRACE, $useStartParenthesisIndex );

                    $this->fixParenthesisInnerEdge( $tokens, $useStartParenthesisIndex, $useEndParenthesisIndex );
                }

                $this->fixParenthesisInnerEdge( $tokens, $index, $endParenthesisIndex );
            }
        }
    }

    private function removeSpaceAroundToken( Tokens $tokens, int $index ) : void
    {
        $token = $tokens[ $index ];

        if( $token->isWhitespace() && ! str_contains( $token->getContent(), "\n" ) ) $tokens->clearAt( $index );
    }

    private function fixParenthesisInnerEdge( Tokens $tokens, int $start, int $end ) : void
    {
        if( $tokens[ $end - 1 ]->isWhitespace() )
        {
            $content = $tokens[ $end - 1 ]->getContent();

            if( $content !== ' ' && ! str_contains( $content, "\n" ) && ! $tokens[ $tokens->getPrevNonWhitespace( $end - 1 ) ]->isComment() )
            {
                $tokens[ $end - 1 ] = new Token( [ T_WHITESPACE, ' ' ] );
            }
        }
        else
        {
            $tokens->insertAt( $end, new Token( [ T_WHITESPACE, ' ' ] ) );
        }

        if( $tokens[ $start + 1 ]->isWhitespace() )
        {
            $content = $tokens[ $start + 1 ]->getContent();

            if( $content !== ' ' && ! str_contains( $content, "\n" ) && ! $tokens[ $tokens->getNextNonWhitespace( $start + 1 ) ]->isComment() )
            {
                $tokens [ $start + 1 ] = new Token( [ T_WHITESPACE, ' ' ] );
            }
        }
        else
        {
            $tokens->insertAt( $start + 1, new Token( [ T_WHITESPACE, ' ' ] ) );
        }
    }

    private function getBlockContent( int $startIndex, int $endIndex, Tokens $tokens ) : array
    {
        $contents = [];

        for( $i = ( $startIndex + 1 ); $i < $endIndex; ++$i )
        {
            $contents[] = $tokens[ $i ]->getContent();
        }

        return $contents;
    }
}
