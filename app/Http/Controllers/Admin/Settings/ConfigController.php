<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Console\Kernel;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Settings\ConfigSettingsFormRequest;

class ConfigController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * @var \Illuminate\Contracts\Console\Kernel
     */
    private $kernel;

    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    private $settings;

    /**
     * ConfigController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                             $alert
     * @param \Illuminate\Contracts\Config\Repository                       $config
     * @param \Illuminate\Contracts\Console\Kernel                          $kernel
     * @param \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface $settings
     */
    public function __construct(
        AlertsMessageBag $alert,
        ConfigRepository $config,
        Kernel $kernel,
        SettingsRepositoryInterface $settings
    ) {
        $this->alert = $alert;
        $this->config = $config;
        $this->kernel = $kernel;
        $this->settings = $settings;
    }

    /**
     * Render Config Panel settings UI.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $showRecaptchaWarning = false;
        if (
            $this->config->get('recaptcha._shipped_secret_key') === $this->config->get('recaptcha.secret_key')
            || $this->config->get('recaptcha._shipped_website_key') === $this->config->get('recaptcha.website_key')
        ) {
            $showRecaptchaWarning = true;
        }

        return view('admin.settings.config', [
            'showRecaptchaWarning' => $showRecaptchaWarning,
        ]);
    }

    /**
     * @param \Pterodactyl\Http\Requests\Admin\Settings\ConfigSettingsFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(ConfigSettingsFormRequest $request): RedirectResponse
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('settings::' . $key, $value);
        }

        $this->kernel->call('queue:restart');
        $this->alert->success('Config settings have been updated successfully and the queue worker was restarted to apply these changes.')->flash();

        return redirect()->route('admin.settings.config');
    }
}
