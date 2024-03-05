<?php

namespace Yeganemehr\WHMCSStubGenerator;

use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\FileGenerator;
use Laminas\Code\Generator\FunctionGenerator;
use Laminas\Code\Reflection\DocBlockReflection;
use Symfony\Component\Finder\Finder;
use Yeganemehr\WHMCSStubGenerator\Generators\ClassGenerator;
use Yeganemehr\WHMCSStubGenerator\Generators\InterfaceGenerator;
use Yeganemehr\WHMCSStubGenerator\Generators\ParameterGenerator;
use Yeganemehr\WHMCSStubGenerator\Generators\TraitGenerator;

class Generator
{
    public static function getClassMemberVisibity(\ReflectionClassConstant|\ReflectionMethod|\ReflectionProperty $entity): string
    {
        if ($entity->isPublic()) {
            return 'public';
        }
        if ($entity->isProtected()) {
            return 'protected';
        }
        if ($entity->isPrivate()) {
            return 'private';
        }

        return '';
    }

    public static function generateDocBlock(\ReflectionClass|\ReflectionMethod|\ReflectionProperty|\ReflectionClassConstant $reflection): ?DocBlockGenerator
    {
        return $reflection->getDocComment() ? DocBlockGenerator::fromReflection(new DocBlockReflection($reflection->getDocComment())) : null;
    }

    public function __construct(protected string $whmcsPath, protected string $outputDir)
    {
    }

    public function generate(): void
    {
        require_once $this->getWHMCSPath('init.php');

        $files = (new Finder())
            ->files()
            ->name('*.php')
            ->in($this->getWHMCSPath('vendor/whmcs/whmcs-foundation/lib'));
        foreach ($files as $file) {
            $path = $file->getPathname();
            if ($path == $this->getWHMCSPath('vendor/whmcs/whmcs-foundation/lib/Mobile.php')) {
                continue;
            }
            require_once $path;
        }

        $functions = get_defined_functions();
        $definitelyWHMCSFunctions = ['sendMessage', 'sendAdminNotification', 'sendAdminNotificationNow', 'sendAdminMessage', 'toMySQLDate', 'validateDateInput', 'fromMySQLDate', 'MySQL2Timestamp', 'getTodaysDate', 'xdecrypt', 'AffiliatePayment', 'calculateAffiliateCommission', 'logActivity', 'addToDoItem', 'generateUniqueID', 'foreignChrReplace', 'foreignChrReplace2', 'getModRewriteFriendlyString', 'sanitize', 'ParseXmlToArray', 'XMLtoARRAY', 'format_as_currency', 'encrypt', '_hash', '_generate_iv', 'getUsersLang', 'swapLang', 'getCurrency', 'formatCurrency', 'currencyDataCache', 'convertCurrency', 'getClientGroups', 'curlCall', 'get_token', 'set_token', 'conditionally_set_token', 'generate_token', 'check_token', 'localAPI_Legacy', 'localAPI', 'redir', 'redirSystemURL', 'logModuleCall', 'updateService', 'autoHyperLink', 'isValidforPath', 'generateNewCaptchaCode', 'escapeJSSingleQuotes', 'recursiveReplace', 'ensurePaymentMethodIsSet', '_safe_serialize', 'safe_serialize', 'upperCaseFirstLetter', 'saveSingleCustomField', 'saveSingleCustomFieldByNameAndType', 'jsonPrettyPrint', 'defineGatewayField', 'defineGatewayFieldStorage', 'generateFriendlyPassword', 'build_query_string', 'routePathWithQuery', 'routePath', 'fqdnRoutePath', 'prependSystemUrlToRoutePath', 'requestedRoutableQueryUriPath', 'view', 'moduleView', 'class_uses_deep', 'traitOf', 'escape', 'stringLiteralToBool', 'valueIsZero', 'arrayTrim', 'removeEmptyValues', 'ucoalesce', 'coalesce', 'ecoalesce', 'scoalesce', 'preparePromotionDataForSelection', 'get_flash_message', 'getLastInput', 'clearLastInput', 'run_hook', 'run_validate_hook', 'convertIniSize', 'getUploadMaxFileSize', 'getIniSettingSizeUnit', 'getIniSettingSize', 'convertBytesToUnit', 'hasMaskedPasswordChanged', 'interpretMaskedPasswordChangeForStorage', 'htmlspecialchars_array'];
        foreach ($functions['user'] as $function) {
            $reflection = new \ReflectionFunction($function);
            if (!in_array($reflection->getName(), $definitelyWHMCSFunctions)) {
                $path = $reflection->getFileName();
                if ('unknown' != $path and str_starts_with($path, $this->getWHMCSPath('vendor')) and !str_starts_with($path, $this->getWHMCSPath('vendor/whmcs'))) {
                    continue;
                }
            }

            $generator = new FunctionGenerator($reflection->getName());
            $parameters = array_map([ParameterGenerator::class, 'fromReflection'], $reflection->getParameters());
            $generator->setParameters($parameters);
            $this->generateFunctionFile($generator);
        }

        $classes = array_merge(get_declared_classes(), get_declared_interfaces(), get_declared_traits());
        $classes = array_filter($classes, fn (string $class) => str_starts_with($class, 'WHMCS\\'));
        foreach ($classes as $class) {
            $reflection = new \ReflectionClass($class);
            if ($reflection->isInterface()) {
                $generator = InterfaceGenerator::fromReflection($reflection);
            } elseif ($reflection->isTrait()) {
                $generator = TraitGenerator::fromReflection($reflection);
            } else {
                $generator = ClassGenerator::fromReflection($reflection);
            }
            $this->generateClassFile($generator);
        }
    }

    public function generateClassFile(ClassGenerator|InterfaceGenerator|TraitGenerator $generator)
    {
        $fileGenerator = new FileGenerator();
        $fileGenerator->setClasses([$generator]);
        $code = $fileGenerator->generate();
        $filename = str_replace('\\', '_', ltrim(implode('\\', [$generator->getNamespaceName(), $generator->getname()]), '\\')).'.php';
        $filename = $this->getOutputPath($filename);
        file_put_contents($filename, $code);
    }

    public function generateFunctionFile(FunctionGenerator $generator)
    {
        $code = "<?php\n".$generator->generate();
        $filename = str_replace('\\', '_', ltrim(implode('\\', [$generator->getNamespaceName(), $generator->getname()]), '\\')).'.php';
        $filename = $this->getOutputPath($filename);
        file_put_contents($filename, $code);
    }

    public function getWHMCSPath(?string $filepath = null): string
    {
        return rtrim($this->whmcsPath, '/').(null !== $filepath ? '/'.$filepath : '');
    }

    public function getOutputPath(?string $filepath = null): string
    {
        return rtrim($this->outputDir, '/').(null !== $filepath ? '/'.$filepath : '');
    }
}
