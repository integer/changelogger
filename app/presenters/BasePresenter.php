<?php

/**
 * Base presenter
 * @link http://wiki.nette.org/cs/cookbook/dynamicke-nacitani-modelu
 * @author Majkl578
 */
abstract class BasePresenter extends \Nette\Application\UI\Presenter
{
    /** @return \ModelLoader */
    final public function getModels()
    {
        return $this->context->modelLoader;
    }
}
