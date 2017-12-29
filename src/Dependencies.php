<?php
namespace Caffeinated\Bonsai;

use Caffeinated\Bonsai\Node;

class Dependencies
{
    /**
     * @var array
     */
    private $nodes = array();

    /**
     * @var array
     */
    private $nodeNames = array();

    /**
     * Constructor method.
     *
     * @param  array  $dependencies
     * @param  bool   $parse
     */
    public function __construct($dependencies = array(), $parse = true)
    {
        $this->nodeNames = array_keys($dependencies);

        if ($parse) {
            $dependencies = $this->parseDependencyList($dependencies);
        }

        foreach ($dependencies as $pair) {
            foreach ($pair as $asset => $dependency) {
                if (! isset($this->nodes[$asset])) {
                    $this->nodes[$asset] = new Node($asset);
                }

                if (! isset($this->nodes[$dependency])) {
                    $this->nodes[$dependency] = new Node($dependency);
                }

                if (! in_array($dependency, $this->nodes[$asset]->children)) {
                    $this->nodes[$asset]->children[] = $dependency;
                }

                if (! in_array($asset, $this->nodes[$dependency]->parents)) {
                    $this->nodes[$dependency]->parents[] = $asset;
                }
            }
        }
    }

    /**
     * Performs topological sorting against the passed array.
     *
     * @return array
     */
    public function sort()
    {
        $nodes     = $this->nodes;
        $rootNodes = array_values($this->getRootNodes($nodes));
        $sorted    = array();

        while (count($nodes) > 0) {
            if ($rootNodes === array()) {
                return array();
            }

            $node     = array_pop($rootNodes);
            $sorted[] = $node->name;

            for ($i = count($node->children) - 1; $i >= 0; $i--) {
                $childNode = $node->children[$i];

                unset($nodes[$node->name]->children[$i]);

                $parentPosition = array_search($node->name, $nodes[$childNode]->parents);

                unset($nodes[$childNode]->parents[$parentPosition]);

                if (! count($nodes[$childNode]->parents)) {
                    array_push($rootNodes, $nodes[$childNode]);
                }
            }

            unset($nodes[$node->name]);
        }

        $looseNodes = array();

        foreach ($this->nodeNames as $name) {
            if (! in_array($name, $sorted)) {
                $looseNodes[] = $name;
            }
        }

        return array_merge($sorted, $looseNodes);
    }

    /**
     * Returns an array of node objects that do not have parents.
     *
     * @param  array  $nodes
     * @return array
     */
    protected function getRootNodes(array $nodes)
    {
        $rootNodes = array();

        foreach ($nodes as $name => $node) {
            if (! count($node->parents)) {
                $rootNodes[$name] = $node;
            }
        }

        return $rootNodes;
    }

    /**
     * Parses a list of dependencies into an array of dependency pairs.
     *
     * @param  array  $list
     * @return array
     */
    protected function parseDependencyList(array $list = array())
    {
        $parsedList = array();

        foreach ($list as $name => $dependencies) {
            foreach ($dependencies as $dependency) {
                array_push($parsedList, [$dependency => $name]);
            }
        }

        return $parsedList;
    }
}
