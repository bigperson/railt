<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Routing\Resolvers;

use Railt\Http\InputInterface;
use Railt\Routing\Route;
use Railt\Routing\Route\Relation;
use Railt\Routing\Store\ObjectBox;

/**
 * Class SingularResolver
 */
class SingularResolver extends BaseResolver
{
    /**
     * @param Route $route
     * @param InputInterface $input
     * @param null|ObjectBox $parent
     * @return array|mixed
     */
    public function call(Route $route, InputInterface $input, ?ObjectBox $parent)
    {
        $this->withParent($route, $input, $parent);

        if (! $parent || $this->isFirstInvocation($input)) {
            $actionResult = $route->call($this->getParameters($input));

            $this->response($route, $input, $actionResult);
        }

        return $this->extract($input, $route, $parent);
    }

    /**
     * @param Route $route
     * @param InputInterface $input
     * @param null|ObjectBox $parent
     */
    protected function withParent(Route $route, InputInterface $input, ?ObjectBox $parent): void
    {
        if ($parent) {
            $box = ObjectBox::rebuild($route, $this->store->getParent($input));

            $input->updateParent($box->getValue(), $box->getResponse());
        }
    }

    /**
     * @param InputInterface $input
     * @return bool
     */
    private function isFirstInvocation(InputInterface $input): bool
    {
        return ! $this->store->has($input);
    }

    /**
     * @param InputInterface $input
     * @param Route $route
     * @param ObjectBox $parent
     * @return array
     */
    private function extract(InputInterface $input, Route $route, ObjectBox $parent): array
    {
        $result = [];

        /** @var ObjectBox $current */
        foreach ($this->store->get($input) as $current) {
            foreach ($route->getRelations() as $relation) {
                if ($this->matched($relation, $parent, $current)) {
                    $result[] = $current;
                }
            }
        }

        return $result;
    }

    /**
     * @param Relation $relation
     * @param ObjectBox $parent
     * @param ObjectBox $current
     * @return bool
     */
    private function matched(Relation $relation, ObjectBox $parent, ObjectBox $current): bool
    {
        if (! $parent->offsetExists($relation->getParentFieldName())) {
            return false;
        }

        if (! $current->offsetExists($relation->getChildFieldName())) {
            return false;
        }

        return $parent[$relation->getParentFieldName()] === $current[$relation->getChildFieldName()];
    }
}
