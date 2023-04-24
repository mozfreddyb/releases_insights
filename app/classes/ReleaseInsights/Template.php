<?php
declare(strict_types=1);

namespace ReleaseInsights;

use Twig\Environment;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader;
use Dotenv\Dotenv;

class Template
{
    public string|bool $template_caching;

    /**
     *  @param array<mixed> $data
     */
    public function __construct(public string $template, public array $data)
    {
        // Cache compiled templates on production
        $dotenv = Dotenv::createImmutable(INSTALL_ROOT);
        $dotenv->safeLoad();
        $this->template_caching = isset($_ENV['TWIG_CACHING']) && $_ENV['TWIG_CACHING'] == 'no' ? false : CACHE_PATH;

        // @codeCoverageIgnoreStart
        // Pass extra variables to template in local dev mode
        if (isset($_ENV['CONTEXT']) && $_ENV['CONTEXT'] == 'local' && !defined('UNIT_TESTING')) {
            $this->data += [
                'branch' => trim((string) shell_exec('git rev-parse --abbrev-ref HEAD')),
            ];
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @codeCoverageIgnore
     */
    public function render(): void
    {
        // Initialize our Templating system
        $twig_loader = new FilesystemLoader(INSTALL_ROOT . 'app/views/templates');
        $twig = new Environment($twig_loader);
        $twig->addExtension(new IntlExtension());

        print $twig->render($this->template, $this->data);
    }
}
