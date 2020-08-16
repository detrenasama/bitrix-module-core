<?php

namespace IO {
    class IO
    {
        static function Ask($message, &$var)
        {
            if (!is_null($var)) {
                echo "$message ({$var}): ";
            } else {
                echo "$message: ";
            }

            try {
                $line = trim(fgets(STDIN));

                $var = strlen($line) ? $line : $var;
            } catch (\Exception $e) {
                self::Say("Error: %s", $e->getMessage());
            }
        }

        static function Say($message, $a1 = null, $a2 = null, $a3 = null, $a4 = null, $a5 = null)
        {
            printf("$message\n", $a1, $a2, $a3, $a4, $a5);
        }
    }

    class Filesystem
    {
        static function CreateFile($path, $content)
        {
            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            file_put_contents($path, $content);
        }
    }

    class Progress
    {
        static $progress = null;

        static function ProgressStart($message, $value = '0%')
        {
            static::$progress = $message;
            self::ProgressUpdate($value);
        }
        static function ProgressEnd()
        {
            if (static::$progress) {
                static::$progress = null;
                fwrite(STDOUT, "\n");
            }
        }
        static function ProgressUpdate($value)
        {
            $mes = substr(static::$progress, 0, TERMINAL_WIDTH - strlen($value) - 1);
            $whitespace = str_repeat(".", TERMINAL_WIDTH - strlen($mes) - strlen($value) - 1);
            fwrite(STDOUT, "\r{$mes}{$whitespace} {$value}");
        }
    }
}

namespace {

    use IO\IO;
    use IO\Filesystem;

    if ($_SERVER['DOCUMENT_ROOT'])
        die("CLI only");

    class Template
    {
        private $params = [];

        public function __construct($params = [])
        {
            $this->params = $params;
        }

        public function render($content)
        {
            return preg_replace_callback('/{{[^}]+}}/', [$this, 'interpolate'], $content);
        }

        private function interpolate($template)
        {
            if (is_array($template))
                $template = reset($template);

            $filters = array_map(function ($e) {
                return trim($e);
            }, explode('|', trim($template, '{}')));

            $var = array_shift($filters);

            $value = $this->arrayValue($var, $this->params);

            if (!empty($filters)) {
                foreach ($filters as $filter) {
                    if (!method_exists($this, "filter_{$filter}"))
                        throw new \Exception("Filter '{$filter}' not found!");

                    $value = call_user_func([$this, "filter_{$filter}"], $value);
                }
            }

            return $value;
        }

        /**
         * @param $var
         * @param $param
         * @return mixed
         * @throws Exception
         */
        private function arrayValue($var, $param)
        {
            if (is_null($var))
                return $param;

            $shiftedKeys = explode('.', $var, 2);
            $key = array_key_exists(0, $shiftedKeys) ? $shiftedKeys[0] : null;
            $more = array_key_exists(1, $shiftedKeys) ? $shiftedKeys[1] : null;

            if (is_array($param) && array_key_exists($key, $param))
                return $this->arrayValue($more, $param[$key]);

            throw new \Exception(sprintf("arrayValue error: Cannot reach '%s' in %s", $var, var_export($param, true)));
        }

        public function filter_slashed($value)
        {
            return addslashes($value);
        }
    }


    $arTemplate = [];

    $arTemplate['/src/Core/Config/Options.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Config;

use Bitrix\Main\Config\Option;

class Options {
    protected \$moduleId;

    public function __construct(\$moduleId)
    {
        \$this->moduleId = \$moduleId;
    }

    /**
     * @return array
     */
	public function defaults()
	{
		return Option::getDefaults(\$this->moduleId);
	}

    /**
     * @return array
     */
	public function real()
	{
		return Option::getForModule(\$this->moduleId);
	}
    public function get(\$name, \$default = null, \$siteId = false)
    {
        return \$this->dbValue(Option::get(\$this->moduleId, \$name, \$default, \$siteId));
    }
    public function set(\$name, \$value = '', \$siteId = '')
    {
        Option::set(\$this->moduleId, \$name, \$this->safeValue(\$value), \$siteId);
    }
	public function delete(array \$filter = [])
	{
		Option::delete(\$this->moduleId, \$filter);
	}


	public function all()
	{
		\$defaults = \$this->defaults();
		\$real = \$this->real();
		\$options = array_merge(\$defaults, \$real);
		return array_map([\$this, 'dbValue'], \$options);
	}

	public function save(\$arOptions)
	{
		\$options = \$this->defaults();
		foreach (\$options as \$key => \$value) {
			if (isset(\$arOptions[\$key]))
				\$this->set(\$key, \$arOptions[\$key]);
			else
				\$this->set(\$key, \$value);
		}
    }

	protected function safeKey(\$key)
	{
		if (strlen(\$key) > 40)
			throw new \InvalidArgumentException("{key} length must be less or equal 40");

		return preg_replace('/[^\d\w\_]/' , '_', \$key);
    }

	protected function safeValue(\$value)
	{
		if (is_object(\$value))
			throw new \InvalidArgumentException("{value} cannot be an object!");

		if (is_array(\$value))
			return serialize(\$value);

		return \$value;
    }

	protected function dbValue(\$value)
	{
		if (is_string(\$value) && is_array(\$res = unserialize(\$value)))
			return \$res;

		return \$value;
    }
}
PHP;

    $arTemplate['/src/Core/Config/Form/FormBuilder.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Config\Form;

use {{ module.namespace }}\Core\Config\Form\Components\Component;
use {{ module.namespace }}\Core\Config\Form\Components\Saveable;
use {{ module.namespace }}\Core\Config\Form\Components\TabComponent;
use {{ module.namespace }}\Module;
use InvalidArgumentException;

class FormBuilder implements Saveable
{
    /** @var Component[] */
    protected \$components = [];

    /** @var TabComponent[] */
    protected \$tabs = [];

    public function add(Component \$component) {
        if (\$component instanceof TabComponent)
            \$this->tabs[] = \$component;
        else {
            if (empty(\$this->tabs))
                throw new InvalidArgumentException("Should be at least 1 tab before adding other components");

            \$component->setTab(end(\$this->tabs));
            \$this->components[] = \$component;
        }
    }

    public function save() {
        foreach (\$this->tabs as \$tab) {
            if (\$tab instanceof Saveable) {
                \$tab->save();
            }
        }

        foreach (\$this->components as \$component) {
            if (\$component instanceof Saveable) {
                \$component->save();
            }
        }
    }

    /**
     * @return void
     */
    public function render()
    {
        global \$APPLICATION;

        \$this->resortComponents(\$this->tabs);
        \$this->resortComponents(\$this->components);

        \$arTabs = [];
        foreach (\$this->tabs as \$tab) {
            \$arTabs[] = \$tab->getArray();
        }

        \$tabControl = new \CAdminTabControl('tabControl', \$arTabs);
        \$tabControl->Begin();

        echo '<form name="' . Module::getModuleId() . '" method="POST" action="' . \$APPLICATION->GetCurPage() . '?mid=' . Module::getModuleId() . '&lang=' . LANGUAGE_ID . '" enctype="multipart/form-data">' . bitrix_sessid_post();

        foreach (\$this->tabs as \$tab) {
            \$tabControl->BeginNextTab();
            \$tab->render();

            \$tabComponents = \array_filter(\$this->components, function (\$e) use (\$tab) {
                return \$e->getTab() === \$tab;
            });

            foreach (\$tabComponents as \$component) {
                \$component->render();
            }
        }

        \$tabControl->Buttons();

        echo     '<input type="submit" name="save" value="Сохранить" />
					<input type="reset" name="reset" value="Отменить" />
                    </form>';

        \$tabControl->End();
    }

    private function resortComponents(&\$components) {
        usort(\$components, [\$this, 'resortComponentHandler']);
    }

    private function resortComponentHandler(Component \$a, Component \$b) {
        if (\$a->getSort() < \$b->getSort())
            return -1;

        if (\$a->getSort() > \$b->getSort())
            return 1;

        return 0;
    }
}
PHP;

    $arTemplate['/src/Core/Config/Form/Components/Component.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Config\Form\Components;

use InvalidArgumentException;

abstract class Component {

    protected \$id;
    protected \$sort;
    protected \$value;
    protected \$params = [];
    protected \$tab;

    public function __construct(string \$id, array \$params, \$value, int \$sort = 0) {
        \$this->id = \$id;
        \$this->sort = \$sort;
        \$this->params = \$params;
        \$this->value = \$value ?: \$params['default'];

        if (!strlen(trim(\$this->id)))
            throw new InvalidArgumentException("Empty {id} provided!");
    }

    public function getId() {
        return \$this->id;
    }

    public function getSort() {
        return \$this->sort;
    }

    public function getTab() {
        return \$this->tab;
    }

    public function setTab(TabComponent \$tab) {
        \$this->tab = \$tab;
    }

    /**
     * Echo HTML
     *
     * @return void
     */
    abstract public function render();
}
PHP;

    $arTemplate['/src/Core/Config/Form/Components/CheckboxComponent.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Config\Form\Components;

use {{ module.namespace }}\Module;

class CheckboxComponent extends LabeledComponent implements Saveable {

    protected function renderInput() {
        ?>
        <input  type="checkbox"
                name="<?= \htmlspecialchars(\$this->id) ?>"
                value="1"
                <?= \$this->value ? 'checked' : '' ?>
                />
        <?
    }

    public function save() {
        \$options = Module::getOptions();

        \$options->set(\$this->getId(), \$_POST[\$this->getId()] ? 1 : 0);
    }
}
PHP;

    $arTemplate['/src/Core/Config/Form/Components/FileComponent.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Config\Form\Components;

use {{ module.namespace }}\Core\Exceptions\NotImplementedException;

class FileComponent extends LabeledComponent implements Saveable {

    protected function renderInput() {
        \$id = \htmlspecialchars(\$this->getId());
        CAdminFileDialog::ShowScript(Array(
            'event' => 'BX_FD_'.\$this->getId(),
            'arResultDest' => Array('FUNCTION_NAME' => 'BX_FD_ONRESULT_'.\$this->getId()),
            'arPath' => Array(),
            'select' => 'F',
            'operation' => 'O',
            'showUploadTab' => true,
            'showAddToMenuTab' => false,
            'fileFilter' => '',
            'allowAllFiles' => true,
            'SaveConfig' => true
        ));
        ?>
        <input  type="text"
                name="<?= \$id ?>"
                id="__FD_PARAM_<?= \$id?>"
                value="<?= \htmlspecialchars(\$this->value) ?>"
                />
        <input value="..." type="button" onclick="window.BX_FD_<?= \$id ?>();" />
        <script>
            setTimeout(function(){
                if (BX("bx_fd_input_<?= \strtolower(\$id) ?>"))
                    BX("bx_fd_input_<?= \strtolower(\$id) ?>").onclick = window.BX_FD_<?= \$id ?>;
            }, 200);
            window.BX_FD_ONRESULT_<?= \$id ?> = function(filename, filepath)
            {
                var oInput = BX("__FD_PARAM_<?= \$id ?>");
                if (typeof filename == "object")
                    oInput.value = filename.src;
                else
                    oInput.value = (filepath + "/" + filename).replace(/\/\//ig, '/');
            }
        </script>
        <?
    }

    public function save() {
        throw new NotImplementedException;
        // TODO: implement file save
    }
}
PHP;

    $arTemplate['/src/Core/Config/Form/Components/HeaderComponent.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Config\Form\Components;

class HeaderComponent extends Component {
    public function __construct(string \$title, int \$sort = 0) {
        parent::__construct(\uniqid('header_'), [], \$title, \$sort);
    }
    public function render() {
        echo '<tr class="heading"><td colspan="2">', \htmlspecialchars(\$this->value), '</td></tr>';
    }
}
PHP;

    $arTemplate['/src/Core/Config/Form/Components/HiddenComponent.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Config\Form\Components;

use {{ module.namespace }}\Module;

class HiddenComponent extends Component implements Saveable {

    public function render() {
        ?>
        <input type="hidden" name="<?= \$this->getId() ?>" value="<?= \htmlspecialchars(\$this->value) ?>" />
        <?
    }

    public function save() {
        \$options = Module::getOptions();

        \$options->set(\$this->getId(), \$_POST[\$this->getId()]);
    }
}
PHP;

    $arTemplate['/src/Core/Config/Form/Components/LabeledComponent.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Config\Form\Components;

abstract class LabeledComponent extends Component {

    public function render() {
        if (\$this->params['label']) {
            echo '<tr><td valign="top" width="40%">', \$this->params['required'] ? '<b>' : '', \$this->params['label'], \$this->params['required'] ? '</b>' : '', '</td><td valign="top" nowrap>';
        } else {
            echo '<tr><td valign="top" colspan="2" align="center">';
        }

        \$this->renderInput();

        echo '</td></tr>';
    }

    abstract protected function renderInput();
}
PHP;

    $arTemplate['/src/Core/Config/Form/Components/MultipleSelectComponent.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Config\Form\Components;

use {{ module.namespace }}\Module;

class MultipleSelectComponent extends LabeledComponent implements Saveable
{
    protected function renderInput()
    {
    ?>
        <select name="<?= \htmlspecialchars(\$this->getId()) ?>[]"
                <? foreach (\$this->params['attributes'] as \$key => \$value) {
                    echo \htmlspecialchars(\$key), '="', \htmlspecialchars(\$value), '" ';
                } ?>
                <?= \$this->params['required'] ? 'required' : '' ?>
                multiple
            >
            <? foreach (\$this->params['values'] as \$value => \$name) : ?>
                <option value="<?= \htmlspecialchars(\$value) ?>" <?= \in_array(\$value, \$this->value) ? 'selected' : '' ?>><?= \htmlspecialchars(\$name) ?></option>
            <? endforeach ?>
        </select>
    <?
    }

    public function save() {
        \$options = Module::getOptions();

        \$options->set(\$this->getId(), \$_POST[\$this->getId()]);
    }
}
PHP;

    $arTemplate['/src/Core/Config/Form/Components/MultipleStringComponent.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Config\Form\Components;

use {{ module.namespace }}\Module;

class MultipleStringComponent extends StringComponent implements Saveable {

    public function render() {
        // TODO: wtf is that?
        foreach (\$this->value as \$val) {
            parent::render();
            \$this->params['label'] = ' ';
        }
        parent::render();
    }

    protected function renderInput() {
        ?>
        <input  type="text"
                name="<?= \htmlspecialchars(\$this->getId()) ?>[]"
                value="<?= \htmlspecialchars(current(\$this->value)) ?>"
                <? foreach (\$this->params['attributes'] as \$key => \$value) {
                    echo \htmlspecialchars(\$key), '="', \htmlspecialchars(\$value), '" ';
                } ?>
                />
        <?
    }

    public function save() {
        \$options = Module::getOptions();

        \$options->set(\$this->getId(), \$_POST[\$this->getId()]);
    }
}
PHP;

    $arTemplate['/src/Core/Config/Form/Components/RightsTabComponent.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Config\Form\Components;

use {{ module.namespace }}\Module;

class RightsTabComponent extends TabComponent implements Saveable
{

    public function __construct()
    {
        parent::__construct('edit_access_tab', [
            'name' => 'Права доступа',
            'title' => 'Настройка прав доступа',
        ], 100);
    }

    public function render()
    {
        global \$APPLICATION;
        \$module_id = Module::getModuleId();

        parent::render();
        require_once(\$_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/admin/group_rights.php");
    }

    public function save() {
        if (isset(\$_REQUEST['RIGHTS']) && isset(\$_REQUEST['GROUPS'])) {
            \$options = Module::getOptions();

            \CMain::DelGroupRight(Module::getModuleId());
            \$GROUP = \$_REQUEST['GROUPS'];
            \$RIGHT = \$_REQUEST['RIGHTS'];

            foreach (\$GROUP as \$k => \$v) {
                if (\$k == 0) {
                    \$options->set('GROUP_DEFAULT_RIGHT', \$RIGHT[0], 'Right for groups by default');
                } else {
                    \CMain::SetGroupRight(Module::getModuleId(), \$GROUP[\$k], \$RIGHT[\$k]);
                }
            }
        }
    }
}
PHP;

    $arTemplate['/src/Core/Config/Form/Components/Saveable.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Config\Form\Components;

interface Saveable {
    public function save();
}
PHP;

    $arTemplate['/src/Core/Config/Form/Components/SelectComponent.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Config\Form\Components;

use {{ module.namespace }}\Module;

class SelectComponent extends LabeledComponent implements Saveable
{
    protected function renderInput()
    {
    ?>
        <select name="<?= \htmlspecialchars(\$this->getId()) ?>"
                <? foreach (\$this->params['attributes'] as \$key => \$value) {
                    echo \htmlspecialchars(\$key), '="', \htmlspecialchars(\$value), '" ';
                } ?>
                <?= \$this->params['required'] ? 'required' : '' ?>
            >
            <? if (!\$this->params['required']) : ?>
                <option value=""><?= \$this->params['no_value_text'] ?></option>
            <? endif ?>
            <? foreach (\$this->params['values'] as \$value => \$name) : ?>
                <option value="<?= \htmlspecialchars(\$value) ?>" <?= \$value == \$this->value ? 'selected' : '' ?>><?= \htmlspecialchars(\$name) ?></option>
            <? endforeach ?>
        </select>
    <?
    }

    public function save() {
        \$options = Module::getOptions();

        \$options->set(\$this->getId(), \$_POST[\$this->getId()]);
    }
}
PHP;

    $arTemplate['/src/Core/Config/Form/Components/StringComponent.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Config\Form\Components;

use {{ module.namespace }}\Module;

class StringComponent extends LabeledComponent implements Saveable {

    protected function renderInput() {
        ?>
        <input  type="text"
                name="<?= \htmlspecialchars(\$this->getId()) ?>"
                value="<?= \htmlspecialchars(\$this->value) ?>"
                <? foreach (\$this->params['attributes'] as \$key => \$value) {
                    echo \htmlspecialchars(\$key), '="', \htmlspecialchars(\$value), '" ';
                } ?>
                />
        <?
    }

    public function save() {
        \$options = Module::getOptions();

        \$options->set(\$this->getId(), \$_POST[(string) \$this->getId()]);
    }
}
PHP;

    $arTemplate['/src/Core/Config/Form/Components/TabComponent.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Config\Form\Components;

use \CAdminTabControl;

class TabComponent extends Component {

    protected \$control;

    public function __construct(string \$id, array \$params, int \$sort = 0) {
        parent::__construct(\$id, \$params, null, \$sort);
    }

    public function setControl(CAdminTabControl \$control) {
        \$this->control = \$control;
    }

    public function getArray() : array {
        return array(
            'DIV' => \$this->getId(),
            'TAB' => \$this->params['name'],
            'ICON' => '',
            'TITLE' => \$this->params['title'] ?: \$this->params['name']
        );
    }

    public function render() {
        // empty
    }
}
PHP;

    $arTemplate['/src/Core/Config/Form/Components/TextComponent.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Config\Form\Components;

use {{ module.namespace }}\Module;

class TextComponent extends LabeledComponent implements Saveable
{
    protected function renderInput()
    {
        ?>
            <textarea   name="<?= \$this->getId() ?>"
                        <? foreach (\$this->params['attributes'] as \$key => \$value) {
                                echo \htmlspecialchars(\$key), '="', \htmlspecialchars(\$value), '" ';
                        } ?>><?= \htmlspecialchars(\$value) ?></textarea>
        <?
    }

    public function save() {
        \$options = Module::getOptions();

        \$options->set(\$this->getId(), \$_POST[\$this->getId()]);
    }
}
PHP;

    $arTemplate['/src/Core/Config/OptionsFactory.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Config;

use {{ module.namespace }}\Module;

class OptionsFactory {
    public function __invoke()
    {
        return new Options(Module::getModuleId());
    }
}
PHP;
    $arTemplate['/src/Core/Exceptions/NotImplementedException.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Exceptions;

class NotImplementedException extends \Exception {}
PHP;
    $arTemplate['/src/Core/Exceptions/ServiceNotFoundException.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

class ServiceNotFoundException extends \Exception implements NotFoundExceptionInterface {}
PHP;
    $arTemplate['/src/Core/Exceptions/ServiceFactoryNotCallableException.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

class ServiceFactoryNotCallableException extends \Exception implements NotFoundExceptionInterface {}
PHP;

    $arTemplate['/src/Core/Log/FileLogHandler.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Log;

class FileLogHandler implements LogHandlerInterface {
    protected \$file;

    /** @var resource */
    protected \$stream;
    protected \$template = "{time} [{level}]: {message}\n";

    public function __construct(\$file)
    {
        \$this->file = (string) \$file;
    }

    public function setTemplate(\$template)
    {
        \$this->template = \$template;
    }

    /**
     * @param \$level
     * @param \$message
     * @param array \$context
     * @throws \Exception
     */
    public function log(\$level, \$message, array \$context = array())
    {
        if (!\$this->stream) {
            if (!file_exists(dirname(\$this->file)))
                mkdir(dirname(\$this->file), 0755, true);

            \$this->stream = fopen(\$this->file, "a+");
            if (!\$this->stream)
                throw new \Exception(sprintf("Cannot write into '%s'!", \$this->file));
        }

        if (\$this->stream)
            \$this->write(\$level, \$message, \$context);
    }

    protected function write(\$level, \$message, array \$context = array())
    {
        fwrite(\$this->stream, \$this->interpolate(\$this->template, array(
            'time' => date("Y-m-d H:i:s"),
            'level' => \$level,
            'message' => \$this->interpolate(\$message, \$context),
        )));
    }

    protected function interpolate(\$message, array \$context = array())
    {
        \$replace = array();
        foreach (\$context as \$key => \$val) {
            if (!is_array(\$val) && (!is_object(\$val) || method_exists(\$val, '__toString'))) {
                \$replace['{' . \$key . '}'] = \$val;
            } else {
                \$replace['{' . \$key . '}'] = var_export(\$val,true);
            }
        }

        return strtr(\$message, \$replace);
    }

    public function __destruct()
    {
        if (\$this->stream)
            fclose(\$this->stream);
    }
}
PHP;
    $arTemplate['/src/Core/Log/Logger.php'] = <<<PHP
<?php
namespace {{ module.namespace }}\Core\Log;

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger {

    protected \$levels_enabled = [];

    /** @var LogHandlerInterface */
    protected \$handler;

    public function setLogHandler(LogHandlerInterface \$handler)
    {
        \$this->handler = \$handler;
    }
    public function setLevelsEnabled(array \$levels)
    {
        \$this->levels_enabled = \$levels;
    }

    public function log(\$level, \$message, array \$context = array())
    {
        if (\in_array(\$level, \$this->levels_enabled) && \$this->handler)
            \$this->handler->log(\$level, \$message, \$context);
    }
}
PHP;
    $arTemplate['/src/Core/Log/LoggerFactory.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Log;

use {{ module.namespace }}\Core\Config\Options;
use Psr\Container\ContainerInterface;

class LoggerFactory {
    public function __invoke(ContainerInterface \$c)
    {
        \$logger = new Logger();
        \$logHandler = new FileLogHandler(\$c->get('log_file'));

        \$logger->setLogHandler(\$logHandler);

        /** @var Options \$options */
        \$options = \$c->get(Options::class);
        \$level = \$options->get('LOG_LEVEL', 0);
        \$logger->setLevel(\$level);

        return \$logger;
    }
}
PHP;
    $arTemplate['/src/Core/Log/LogHandlerInterface.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Log;

interface LogHandlerInterface {
    public function log(\$level, \$message, array \$context = array());
}
PHP;

    $arTemplate['/src/Core/Service/Container.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Service;

use Psr\Container\ContainerInterface;
use {{ module.namespace }}\Core\Exceptions\ServiceNotFoundException;
use {{ module.namespace }}\Core\Exceptions\ServiceFactoryNotCallableException;

class Container implements ContainerInterface {
    protected \$services = [];
    protected \$factories = [];
    protected \$cache = [];

    public function __construct(\$params = [])
    {
        \$this->services = \$params;
    }

    /**
     * @param string \$id
     * @return mixed
     * @throws ServiceNotFoundException
     * @throws ServiceFactoryNotCallableException
     * @throws \ReflectionException
     */
    public function get(\$id)
    {
        if (array_key_exists(\$id, \$this->cache))
            return \$this->cache[\$id];

        if (array_key_exists(\$id, \$this->factories)) {
            \$this->cache[\$id] = \$this->doFactory(\$id);
            return \$this->cache[\$id];
        }

        if (!array_key_exists(\$id, \$this->services)) {
            \$this->cache[\$id] = \$this->doReflection(\$id);
            return \$this->cache[\$id];
        }

        \$this->cache[\$id] = \$this->services[\$id];
        return \$this->cache[\$id];
    }

    public function has(\$id)
    {
        return array_key_exists(\$id, \$this->services) || class_exists(\$id);
    }

    public function setDependencies(array \$dependencies)
    {
        if (is_array(\$dependencies['factories']))
            \$this->factories = \$dependencies['factories'];
    }


    /**
     * @param string \$id
     * @return mixed
     * @throws ServiceFactoryNotCallableException
     */
    protected function doFactory(\$id)
    {
        if (is_array(\$this->factories[\$id]) && is_callable(\$this->factories[\$id]))
            return \$this->factories[\$id](\$this);

        if (is_callable(\$this->factories[\$id]))
            return \$this->factories[\$id](\$this);

        try {
            \$reflection = new \ReflectionClass(\$this->factories[\$id]);

            /** @var callable \$factory */
            \$factory = \$reflection->newInstance();
            return call_user_func(\$factory, \$this);

        } catch (\ReflectionException \$e) {
            // ID is not a class
            throw new ServiceFactoryNotCallableException("Factory '".var_export(\$this->factories[\$id],true)."' is not found or not callable!");
        }
    }

    /**
     * @param \$id
     * @return object
     * @throws ServiceFactoryNotCallableException
     * @throws ServiceNotFoundException
     * @throws \ReflectionException
     */
    protected function doReflection(\$id)
    {
        if (!class_exists(\$id))
            throw new ServiceNotFoundException('Unknown service "' . \$id . '"');

        \$reflection = new \ReflectionClass(\$id);
        \$arguments = [];
        if ((\$constructor = \$reflection->getConstructor()) !== null) {
            foreach (\$constructor->getParameters() as \$parameter) {
                if (\$paramClass = \$parameter->getClass()) {
                    \$arguments[] = \$this->get(\$paramClass->getName());
                } elseif (\$parameter->isArray()) {
                    \$arguments[] = [];
                    } else {
                    if (!\$parameter->isDefaultValueAvailable())
                        throw new ServiceNotFoundException('Unable to resolve "' . \$parameter->getName() . '" in service "' . \$id . '"');

                    \$arguments[] = \$parameter->getDefaultValue();
                }
            }
    }

        return \$reflection->newInstanceArgs(\$arguments);
    }
}
PHP;

    $arTemplate['/src/Core/BaseModule.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use {{ module.namespace }}\Core\Config\Options;
use {{ module.namespace }}\Core\Service\Container;

abstract class BaseModule
{
	protected static \$container;

	/**
	 * @return ContainerInterface
	 */
	public static function getContainer()
	{
		if (!static::\$container) {
            \$config = new ConfigAggregator();

            \$cache = static::getConfigCacheFile();
            if (!is_file(\$cache)) {
                \$config->set([
                    \$config->file(static::getConfigFile()),
                    \$config->file(static::getLocalConfigFile()),
                ]);

                if (\$config->get()['cache_config']) {
                    if (!is_dir(dirname(\$cache)))
                        mkdir(dirname(\$cache), 0755, true);

                    file_put_contents(\$cache, '<?php return ' . var_export(\$config->get(), true) . ';');
				}
            } else {
                \$config->set([require(\$cache)]);
			}

            \$configData = \$config->get();
            static::\$container = new Container(\$configData);
            if (is_array(\$configData['dependencies']))
                static::\$container->setDependencies(\$configData['dependencies']);
		}
        return static::\$container;
	}

	/**
	 * @return Options
	 */
	public static function getOptions()
	{
		return static::getContainer()->get(Options::class);
	}

	protected static function getConfigFile()
	{
        return static::getModuleDir() . "/config.php";
	}

    public static function getLocalConfigFile()
	{
        \$files = glob(\$_SERVER['DOCUMENT_ROOT'] . "/{local,bitrix}/config/" . static::getModuleId() . "/config{.cache,}.php", GLOB_BRACE);
        return isset(\$files[0]) ? \$files[0] : '';
    }

    public static function getConfigCacheFile()
    {
        return \$_SERVER['DOCUMENT_ROOT'] . '/bitrix/cache/' . static::getModuleId() . '/config.php';
	}

	/**
	 * @return LoggerInterface
	 */
	public static function getLogger()
	{
		return static::getContainer()->get(LoggerInterface::class);
	}

	/**
	 * @return string
	 */
    public static function getModuleId()
    {
        return basename(static::getModuleDir());
    }

    public static function getModuleDir()
    {
        return dirname(dirname(__DIR__));
	}
}
PHP;

    $arTemplate['/src/Core/ConfigAggregator.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core;

class ConfigAggregator
{
    protected \$config = [];

    /**
     * @param string \$filepath
     * @return array
     */
    public function file(string \$filepath)
    {
        if (is_file(\$filepath))
            return (array)@include(\$filepath);

        return [];
    }

    /**
     * @param array[][] \$configs
     */
    public function set(array \$configs)
    {
        \$configs = array_filter(\$configs, function (\$e) {
            return is_array(\$e);
        });

        \$this->config = \$this->merge(...\$configs);
    }

    /**
     * @return array
     */
    public function get()
    {
        return \$this->config;
    }

    /**
     * @param mixed ...\$configs
     * @return array
     */
    protected function merge(...\$configs)
    {
        \$merged = array();
        foreach (\$configs as \$config) {
            foreach (\$config as \$key => \$value) {
                if (!array_key_exists(\$key, \$merged)) {
                    \$merged[\$key] = \$value;
                    continue;
                }

                if (is_array(\$merged[\$key])) {

                    if (!is_array(\$value)) {
                        \$merged[\$key][] = \$value;
                        continue;
                    }

                    if (\$this->isList(\$value)) {
                        \$merged[\$key] = array_merge(\$merged[\$key], \$value);
                        continue;
                    }

                    \$merged[\$key] = \$this->merge(\$merged[\$key], \$value);
                    continue;
                }

                if (is_array(\$value)) {
                    \$merged[\$key] = \$this->merge(array(\$merged[\$key]), \$value);
                    continue;
                }

                \$merged[\$key] = \$value;
            }
        }
        return \$merged;
    }

    protected function isList(array \$value)
    {
        return array_keys(\$value) === range(0, count(\$value) - 1);
    }
}
PHP;
    $arTemplate['/src/Core/Installer.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\IO\Directory;

abstract class Installer extends \CModule
{
    public \$MODULE_ID;
    public \$MODULE_VERSION;
    public \$MODULE_VERSION_DATE;
    public \$MODULE_NAME;
    public \$MODULE_DESCRIPTION;
    public \$MODULE_GROUP_RIGHTS;

    public \$PARTNER_NAME;
    public \$PARTNER_URI;

    public function __construct()
    {
        \$dir = \$this->getModuleDir();
        \$this->MODULE_ID = basename(\$dir);

        \$arModuleVersion = array();
        include(\$dir . "/install/version.php");
        \$this->MODULE_VERSION = \$arModuleVersion["VERSION"];
        \$this->MODULE_VERSION_DATE = \$arModuleVersion["VERSION_DATE"];
    }

    public function DoInstall()
    {
        ModuleManager::registerModule(\$this->MODULE_ID);
        \$this->InstallFiles();
    }

    public function DoUninstall()
    {
        \$this->UnInstallFiles();
        ModuleManager::unregisterModule(\$this->MODULE_ID);
    }

    public function InstallFiles()
    {
        // Components path mask: MODULE_PATH/install/components/MODULE_NAME.COMPONENT_NAME
        // Where MODULE_NAME in module '[vendor].[module]' is '[module]'

        \$components = \$this->GetModuleDir()."/install/components";
        if (Directory::isDirectoryExists(\$components)){
            CopyDirFiles(\$components, Application::getDocumentRoot() . "/bitrix/components/{\$this->getVendor()}/", true, true);
        }

        // Directories will be copied into /bitrix/DIRECTORY_NAME/MODULE_ID/

        \$files = glob(\$this->GetModuleDir()."/install/files/*/");
        foreach (\$files as \$dir) {
            \$basename = basename(\$dir);
            CopyDirFiles(\$dir, Application::getDocumentRoot() . "/bitrix/{\$basename}/{\$this->MODULE_ID}/", true, true);
        }

        // Files will be copied into /bitrix/admin/

        \$files = glob(\$this->GetModuleDir()."/install/admin/*.php");
        foreach (\$files as \$file) {
            \$basename = \$this->MODULE_ID . '_' . basename(\$file);
            CopyDirFiles(\$file, Application::getDocumentRoot() . "/bitrix/admin/{\$basename}", true, true);
        }
    }

    public function UnInstallFiles()
    {
        \$components = glob(Application::getDocumentRoot()."/bitrix/components/{\$this->getVendor()}/{\$this->getModuleCode()}.*/");
        foreach (\$components as \$dir)
            Directory::deleteDirectory(\$dir);

        \$files = glob(\$this->GetModuleDir()."/install/files/*/");
        foreach (\$files as \$dir) {
            \$basename = basename(\$dir);
            Directory::deleteDirectory(Application::getDocumentRoot()."/bitrix/{\$basename}/{\$this->MODULE_ID}");
        }

        \$files = glob(\$this->GetModuleDir()."/install/admin/*.php");
        foreach (\$files as \$file) {
            \$basename = \$this->MODULE_ID . '_' . basename(\$file);
            unlink(Application::getDocumentRoot()."/bitrix/admin/{\$basename}");
        }
    }

    /**
     * @return string
     */
    private function getModuleDir()
    {
        return dirname(dirname(__DIR__));
    }

    /**
     * @return string
     */
    private function getVendor()
    {
        return (string) substr(\$this->MODULE_ID, 0, strpos(\$this->MODULE_ID, '.'));
    }

    /**
     * @return string
     */
    private function getModuleCode()
    {
        return (string) substr(\$this->MODULE_ID, strpos(\$this->MODULE_ID, '.')+1);
    }
}
PHP;

    $arTemplate['/install/index.php'] = <<<PHP
<?php defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use {{ module.namespace }}\Core\Installer;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

if (class_exists('{{ module.class }}')) {
    return;
}

include_once(dirname(__DIR__) . '/src/Core/Installer.php');

class {{ module.class }} extends Installer {
    public function __construct()
    {
        parent::__construct();

        \$this->MODULE_NAME = Loc::getMessage("{{ lang.prefix }}_MODULE_NAME");
        \$this->MODULE_DESCRIPTION = Loc::getMessage("{{ lang.prefix }}_MODULE_DESCRIPTION");
        \$this->PARTNER_NAME = Loc::getMessage("{{ lang.prefix }}_COMPANY_NAME");
        \$this->PARTNER_URI = Loc::getMessage("{{ lang.prefix }}_PARTNER_URI");
    }

    public function DoInstall()
    {
        parent::DoInstall();

        // Your code here, e.g.:
        \$this->InstallDB();
        // \$this->InstallEvents();
    }

    public function DoUninstall()
    {
        // Your code here, e.g.:
        // \$this->UnInstallEvents();
        \$this->UnInstallDB();

        parent::DoUninstall();
    }

    public function InstallDB()
	{
		global \$APPLICATION, \$DB;
		// \$this->errors = \$DB->RunSQLBatch(__DIR__.'/batch/db/'.strtolower(\$DB->type).'/install.sql');

		if (is_array(\$this->errors))
		{
			\$APPLICATION->ThrowException(implode(' ', \$this->errors));
			return false;
		}

		return true;
	}

	public function uninstallDB()
	{
		global \$APPLICATION, \$DB;

		if (!Option::get(\$this->MODULE_ID, 'UNINSTALL_SAVE_SETTINGS', 1)) {
			// \$this->errors = \$DB->RunSQLBatch(__DIR__.'/batch/db/'.strtolower(\$DB->type).'/uninstall.sql');
		}

		if (is_array(\$this->errors))
		{
			\$APPLICATION->ThrowException(implode(' ', \$this->errors));
			return false;
		}

		return true;
	}

}

// Need to be closed because of Bitrix obfuscation
?>
PHP;
    $arTemplate['/install/version.php'] = <<<PHP
<?php
\$arModuleVersion = array(
	"VERSION" => "{{ module.version }}",
	"VERSION_DATE" => "{{ module.versionDate }}"
);
PHP;

    $arTemplate['/lang/ru/options.php'] = <<<PHP
<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

\$MESS['{{ lang.prefix }}_MAIN'] = "Основные настройки";
\$MESS['{{ lang.prefix }}_ACTIVE'] = "Модуль активен";
\$MESS['{{ lang.prefix }}_UNINSTALL_SAVE_SETTINGS'] = "Сохранить настройки при удалении модуля";
\$MESS['{{ lang.prefix }}_SETTINGS_MODULE'] = "Основные настройки";

\$MESS['{{ lang.prefix }}_LOG_LEVEL'] = "Логировать";
\$MESS['{{ lang.prefix }}_LOG_LEVEL_NOTICE'] = "Сообщения";
\$MESS['{{ lang.prefix }}_LOG_LEVEL_INFO'] = "Информация";
\$MESS['{{ lang.prefix }}_LOG_LEVEL_WARNING'] = "Предупреждения";
\$MESS['{{ lang.prefix }}_LOG_LEVEL_ALERT'] = "Тревоги";
\$MESS['{{ lang.prefix }}_LOG_LEVEL_ERROR'] = "Ошибки";
\$MESS['{{ lang.prefix }}_LOG_LEVEL_CRITICAL'] = "Критичные";
\$MESS['{{ lang.prefix }}_LOG_LEVEL_EMERGENCY'] = "Служебные";
\$MESS['{{ lang.prefix }}_LOG_LEVEL_DEBUG'] = "Отладочные";

PHP;
    $arTemplate['/lang/ru/install/index.php'] = <<<PHP
<?php
\$MESS["{{ lang.prefix }}_MODULE_NAME"] = "{{ module.name }}";
\$MESS["{{ lang.prefix }}_MODULE_DESCRIPTION"] = "{{ module.description}}";
\$MESS["{{ lang.prefix }}_COMPANY_NAME"] = "{{ vendor.name }}";
\$MESS["{{ lang.prefix }}_PARTNER_URI"] = "{{ vendor.site }}";
PHP;

    $arTemplate['/lib/controller/data.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Connection extends Controller
{
    public function configureActions()
    {
        return [
            'some' => [
                'prefilters' => []
            ],
        ];
    }

    public function someAction()
    {

        return array('success' => true);
    }
}
PHP;

    $arTemplate['/vendor/Psr/Container/src/ContainerInterface.php'] = <<<PHP
<?php

namespace Psr\Container;

interface ContainerInterface {
	public function get(\$id);
	public function has(\$id);
}
PHP;
    $arTemplate['/vendor/Psr/Container/src/NotFoundExceptionInterface.php'] = <<<PHP
<?php

namespace Psr\Container;

interface NotFoundExceptionInterface {}
PHP;
    $arTemplate['/vendor/Psr/Log/src/LoggerInterface.php'] = <<<PHP
<?php

namespace Psr\Log;

/**
 * Describes a logger instance.
 *
 * The message MUST be a string or object implementing __toString().
 *
 * The message MAY contain placeholders in the form: {foo} where foo
 * will be replaced by the context data in key "foo".
 *
 * The context array can contain arbitrary data, the only assumption that
 * can be made by implementors is that if an Exception instance is given
 * to produce a stack trace, it MUST be in a key named "exception".
 *
 * See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 * for the full interface specification.
 */
interface LoggerInterface
{
    /**
     * System is unusable.
     *
     * @param string \$message
     * @param array \$context
     * @return void
     */
    public function emergency(\$message, array \$context = array());

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string \$message
     * @param array \$context
     * @return void
     */
    public function alert(\$message, array \$context = array());

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string \$message
     * @param array \$context
     * @return void
     */
    public function critical(\$message, array \$context = array());

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string \$message
     * @param array \$context
     * @return void
     */
    public function error(\$message, array \$context = array());

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string \$message
     * @param array \$context
     * @return void
     */
    public function warning(\$message, array \$context = array());

    /**
     * Normal but significant events.
     *
     * @param string \$message
     * @param array \$context
     * @return void
     */
    public function notice(\$message, array \$context = array());

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string \$message
     * @param array \$context
     * @return void
     */
    public function info(\$message, array \$context = array());

    /**
     * Detailed debug information.
     *
     * @param string \$message
     * @param array \$context
     * @return void
     */
    public function debug(\$message, array \$context = array());

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed \$level
     * @param string \$message
     * @param array \$context
     * @return void
     */
    public function log(\$level, \$message, array \$context = array());
}
PHP;
    $arTemplate['/vendor/Psr/Log/src/AbstractLogger.php'] = <<<PHP
<?php

namespace Psr\Log;

/**
 * This is a simple Logger implementation that other Loggers can inherit from.
 *
 * It simply delegates all log-level-specific methods to the `log` method to
 * reduce boilerplate code that a simple Logger that does the same thing with
 * messages regardless of the error level has to implement.
 */
abstract class AbstractLogger implements LoggerInterface
{
    /**
     * System is unusable.
     *
     * @param string \$message
     * @param array  \$context
     *
     * @return void
     */
    public function emergency(\$message, array \$context = array())
    {
        \$this->log(LogLevel::EMERGENCY, \$message, \$context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string \$message
     * @param array  \$context
     *
     * @return void
     */
    public function alert(\$message, array \$context = array())
    {
        \$this->log(LogLevel::ALERT, \$message, \$context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string \$message
     * @param array  \$context
     *
     * @return void
     */
    public function critical(\$message, array \$context = array())
    {
        \$this->log(LogLevel::CRITICAL, \$message, \$context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string \$message
     * @param array  \$context
     *
     * @return void
     */
    public function error(\$message, array \$context = array())
    {
        \$this->log(LogLevel::ERROR, \$message, \$context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string \$message
     * @param array  \$context
     *
     * @return void
     */
    public function warning(\$message, array \$context = array())
    {
        \$this->log(LogLevel::WARNING, \$message, \$context);
    }

    /**
     * Normal but significant events.
     *
     * @param string \$message
     * @param array  \$context
     *
     * @return void
     */
    public function notice(\$message, array \$context = array())
    {
        \$this->log(LogLevel::NOTICE, \$message, \$context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string \$message
     * @param array  \$context
     *
     * @return void
     */
    public function info(\$message, array \$context = array())
    {
        \$this->log(LogLevel::INFO, \$message, \$context);
    }

    /**
     * Detailed debug information.
     *
     * @param string \$message
     * @param array  \$context
     *
     * @return void
     */
    public function debug(\$message, array \$context = array())
    {
        \$this->log(LogLevel::DEBUG, \$message, \$context);
    }
}
    
PHP;
    $arTemplate['/vendor/Psr/Log/src/NullLogger.php'] = <<<PHP
<?php

namespace Psr\Log;

/**
 * This Logger can be used to avoid conditional log calls.
 *
 * Logging should always be optional, and if no logger is provided to your
 * library creating a NullLogger instance to have something to throw logs at
 * is a good way to avoid littering your code with `if ($this->logger) { }`
 * blocks.
 */
class NullLogger extends AbstractLogger
{
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  \$level
     * @param string \$message
     * @param array  \$context
     *
     * @return void
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log(\$level, \$message, array \$context = array())
    {
        // noop
    }
}  
PHP;
    $arTemplate['/vendor/Psr/Log/src/LogLevel.php'] = <<<PHP
<?php

namespace Psr\Log;

/**
 * Describes log levels.
 */
class LogLevel
{
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const WARNING   = 'warning';
    const ALERT     = 'alert';
    const ERROR     = 'error';
    const CRITICAL  = 'critical';
    const EMERGENCY = 'emergency';
    const DEBUG     = 'debug';
}
PHP;
    $arTemplate['/vendor/autoload.php'] = <<<PHP
<?php
spl_autoload_register(function (\$class) {
	\$moduleNamespace = '{{ module.namespace | slashed }}\\\\';
	if (strpos(\$class, \$moduleNamespace) === 0) {
		\$path = __DIR__ . '/src/' . strtr(substr(\$class, strlen(\$moduleNamespace)), '\\\\', '/') . '.php';
		if (is_file(\$path))
			require_once \$path;
	} else {
	    list(\$vendor, \$library, \$file) = explode('\\\\', \$class, 3);
		\$path = __DIR__ . "/vendor/{\$vendor}/{\$library}/src/" . strtr(\$file, '\\\\', '/') . '.php';
		if (is_file(\$path))
			require_once \$path;
	}
});
PHP;

    $arTemplate['/.settings.php'] = <<<PHP
<?php
return [
    'controllers' => [
        'value' => [
            'namespaces' => [
                '\{{ module.namespace }}\Controller' => 'api',
            ],
        ],
        'readonly' => true,
    ],
];
PHP;
    $arTemplate['/config.php'] = <<<PHP
<?php

use {{ module.namespace }}\Core\Config\Options;
use {{ module.namespace }}\Core\Config\OptionsFactory;
use {{ module.namespace }}\Core\Log\LoggerFactory;
use Psr\Log\LoggerInterface;

return [
    'dependencies' => [
        'factories' => [
            Options::class => OptionsFactory::class,
            LoggerInterface::class => LoggerFactory::class,
        ]
    ],
    'log_file' => __DIR__ . '/logs/module-log.txt',
    'cache_config' => false,
];
PHP;

    $arTemplate['/default_option.php'] = <<<PHP
<?php

\${{ module.class }}_default_option = array(
	'ACTIVE' => 0,
	'UNINSTALL_SAVE_SETTINGS' => 0,
	'LOG_LEVEL' => [],
);
PHP;

    $arTemplate['/include.php'] = <<<PHP
<?php

namespace {{ module.namespace }};

use {{ module.namespace }}\Core\BaseModule;

require_once(__DIR__ . '/vendor/autoload.php');

final class Module extends BaseModule {

}

// Need to be closed because of Bitrix obfuscation
?>
PHP;

    $arTemplate['/options.php'] = <<<PHP
<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use {{ module.namespace }}\Core\Config\Form\Components;
use {{ module.namespace }}\Core\Config\Form\FormBuilder;
use {{ module.namespace }}\Module;
use Psr\Log\LogLevel;

Loc::loadMessages(\$_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/options.php");
Loc::loadMessages(__FILE__);
\$module_id = basename(__DIR__);
\$module_state = Loader::includeSharewareModule(\$module_id);
if (\$module_state === Loader::MODULE_DEMO_EXPIRED) {
    echo Loc::getMessage("MODULE_EXPIRED_DESCRIPTION_LINK");
    return;
}
ClearVars();

\$container = Module::getContainer();
\$options = Module::getOptions();
\$arOptions = \$options->all();

\$form = new FormBuilder();

\$form->add(new Components\TabComponent('main_tab', [
    'name' => Loc::getMessage("{{ lang.prefix }}_SETTINGS_MODULE")
]));

\$form->add(new Components\HeaderComponent(Loc::getMessage("{{ lang.prefix }}_MAIN")));

\$form->add(new Components\CheckboxComponent('ACTIVE', [
    'label' => Loc::getMessage("{{ lang.prefix }}_ACTIVE"),
], \$arOptions['ACTIVE']));

\$form->add(new Components\CheckboxComponent('UNINSTALL_SAVE_SETTINGS', [
    'label' => Loc::getMessage("{{ lang.prefix }}_UNINSTALL_SAVE_SETTINGS"),
], \$arOptions['UNINSTALL_SAVE_SETTINGS']));

\$form->add(new Components\MultipleSelectComponent('LOG_LEVELS', [
    'label' => Loc::getMessage("{{ lang.prefix }}_LOG_LEVELS"),
    'values' => [
        LogLevel::NOTICE => Loc::getMessage("{{ lang.prefix }}_LOG_LEVEL_NOTICE"),
        LogLevel::INFO => Loc::getMessage("{{ lang.prefix }}_LOG_LEVEL_INFO"),
        LogLevel::WARNING => Loc::getMessage("{{ lang.prefix }}_LOG_LEVEL_WARNING"),
        LogLevel::ALERT => Loc::getMessage("{{ lang.prefix }}_LOG_LEVEL_ALERT"),
        LogLevel::ERROR => Loc::getMessage("{{ lang.prefix }}_LOG_LEVEL_ERROR"),
        LogLevel::CRITICAL => Loc::getMessage("{{ lang.prefix }}_LOG_LEVEL_CRITICAL"),
        LogLevel::EMERGENCY => Loc::getMessage("{{ lang.prefix }}_LOG_LEVEL_EMERGENCY"),
        LogLevel::DEBUG => Loc::getMessage("{{ lang.prefix }}_LOG_LEVEL_DEBUG"),
    ]
], \$arOptions['LOG_LEVELS']));


\$form->add(new Components\RightsTabComponent);

// Save form
if (\$_SERVER["REQUEST_METHOD"] == "POST" && (\$_REQUEST["save"] <> '') && check_bitrix_sessid()) {
    \$form->save();

    LocalRedirect(\$APPLICATION->GetCurPageParam());
    exit;
}

// Render form
\$form->render();
PHP;

    #Asking user
    IO::Say("Module maker CLI v%s", '0.6');
    $inputs = [
        'module' => [
            'id' => 'local.lib',
            'version' => '1.0.0',
            'name' => 'Module example',
            'description' => 'Boilerplate for module',
        ],
        'lang' => [
            'prefix' => 'CW_LL',
        ],
        'vendor' => [
            'name' => 'Ctweb',
            'site' => 'https://ctweb.ru',
        ]
    ];

    $confirm = 'y';
    do {
        do {
            IO::Ask('Module namespace (ex.: Vendor\\ModuleName)', $inputs['module']['namespace']);
        } while (!preg_match("/^[A-Z][\w\d]+\\\\[A-Z][\w\d]+$/", $inputs['module']['namespace']) !== false);

        $inputs['lang']['prefix'] = join('_', array_map(function ($e) {
            $expr = '/[A-Z]/';
            preg_match_all($expr, $e, $matches);
            $result = implode('', $matches[0]);
            return $result;
        }, explode('\\', $inputs['module']['namespace'])));

        IO::Ask("Lang prefix", $inputs['lang']['prefix']);
        IO::Ask("Version", $inputs['module']['version']);

        IO::Ask("Name", $inputs['module']['name']);
        IO::Ask("Description", $inputs['module']['description']);

        $inputs['vendor']['name'] = explode('\\', $inputs['module']['namespace'])[0];
        $inputs['vendor']['site'] = 'https://' . strtolower($inputs['vendor']['name']) . '.ru';

        IO::Ask("Vendor", $inputs['vendor']['name']);
        IO::Ask("Vendor site", $inputs['vendor']['site']);

        $inputs['module']['id'] = strtolower(str_replace('\\', '.', $inputs['module']['namespace']));
        $inputs['module']['class'] = strtr($inputs['module']['id'], '.', '_');

        $inputs['module']['versionDate'] = date_format(date_create(), 'Y-m-d H:i:s');

        #
        #   Confirm info
        #
        IO::Say("Installing module:");
        IO::Say("Module id: %s", $inputs['module']['id']);
        IO::Say("Module name: %s", $inputs['module']['name']);
        IO::Say("Module description: %s\n", $inputs['module']['description']);

        IO::Say("Module class: %s", $inputs['module']['class']);
        IO::Say("Module namespace: %s\n", $inputs['module']['namespace']);

        IO::Say("Version: %s", $inputs['module']['version']);
        IO::Say("Version date: %s\n", $inputs['module']['versionDate']);

        IO::Say("Vendor: %s", $inputs['vendor']['name']);
        IO::Say("Vendor site: %s\n", $inputs['vendor']['site']);

        IO::Say("Lang prefix: %s\n", $inputs['lang']['prefix']);

        IO::Ask("Is information correct? (Y|n)", $confirm);
    } while (trim(strtolower($confirm)) !== 'y');

    #Generate files
    $module_dir = __DIR__ . "/{$inputs['module']['id']}";

    $renderer = new Template($inputs);

    foreach ($arTemplate as $path => $template) {

        $compiledTemplate = $renderer->render($template);

        $path = (strpos($path, '/') === 0) ? $path : "/{$path}";
        Filesystem::CreateFile($module_dir . $path, $compiledTemplate);
    }
}
