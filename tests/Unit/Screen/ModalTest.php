<?php

declare(strict_types=1);

namespace Orchid\Tests\Unit\Screen;

use Orchid\Tests\TestUnitCase;
use Orchid\Screen\Layouts\Modal;
use Illuminate\Support\Facades\Validator;
use Orchid\Tests\Exemplar\App\Screens\ModalScreen;

/**
 * Class ModalTest.
 */
class ModalTest extends TestUnitCase
{
    /**
     * @throws \Throwable
     */
    public function testModalScreenRender()
    {
        $screen = new ModalScreen();
        $html = $screen->view()->withErrors(Validator::make([], []))->render();

        $this->assertStringContainsString($screen->name, $html);
        $this->assertStringContainsString($screen->description, $html);

        $this->assertStringContainsString(ModalScreen::TITLE_MODAL, $html);
        $this->assertStringContainsString(ModalScreen::APPLY_BUTTON, $html);
        $this->assertStringContainsString(ModalScreen::CLOSE_BUTTON, $html);

        $this->assertStringContainsString(Modal::SIZE_LG, $html);
    }
}
