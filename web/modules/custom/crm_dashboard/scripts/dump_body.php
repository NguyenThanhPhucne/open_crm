<?php
$filepath = '/tmp/node_146_body.html';
if (!file_exists($filepath)) {
  $n = \Drupal\node\Entity\Node::load(146);
  $b = $n->get('body')->value;
  file_put_contents($filepath, $b);
}
echo file_get_contents($filepath);
