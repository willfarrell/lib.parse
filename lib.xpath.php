<?php


/**
 * calculateXPath function.
 *
 * @param DOMNode $node __comment_missing__
 *
 * @return void
 * @access public
 */
function calculateXPath(DOMNode $node)
{
    $q     = new DOMXPath($node->ownerDocument);
    $xpath = '';

    do {
        $position = 1 + $q->query('preceding-sibling::*[name()="' . $node->nodeName . '"]', $node)->length;
        $xpath    = '/' . $node->nodeName . '[' . $position . ']' . $xpath;
        $node     = $node->parentNode;
    } while (!$node instanceof DOMDocument);

    return $xpath;
}

?>