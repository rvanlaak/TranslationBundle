<?php
/*
* This file is part of the PierstovalTranslationBundle package.
*
* (c) Alexandre "Pierstoval" Rock Ancelet <pierstoval@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Pierstoval\Bundle\TranslationBundle\Test\Controller;

use Pierstoval\Bundle\TranslationBundle\Tests\Fixtures\AbstractTestCase;
use Pierstoval\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\DomCrawler\Field\TextareaFormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Router;

class TranslationControllerTest extends AbstractTestCase {

    public function testAdminAndExport()
    {
        $client = static::createClient();

        /** @var Router $urlGenerator */
        $urlGenerator = $client->getContainer()->get('router');

        $crawler = $client->request("GET", '/admin/translations/');

        $exportLink = $crawler->filter('div.container > h1 + a');
        $translationsContainer = $crawler->filter('div.container .row > div.alert');

        $this->assertContains('alert-danger', $translationsContainer->attr('class'));
        $this->assertEquals($urlGenerator->generate('pierstoval_translation_export', array(), UrlGenerator::ABSOLUTE_URL), $exportLink->attr('href'));
        $this->assertGreaterThan(0, $exportLink->count());

        $crawler->clear();
        $crawler = $client->click($exportLink->link('GET'));

        $refreshLink = $urlGenerator->generate('pierstoval_translation_adminlist', array(), UrlGenerator::ABSOLUTE_PATH);
        $this->assertContains('Redirecting to '.$refreshLink, $crawler->html());

        $crawler->clear();
    }

    public function testTranslationListAndEdit()
    {
        $client = static::createClient();

        $container = $client->getContainer();

        /** @var Translator $translator */
        $translator = $container->get('pierstoval_translator');
        $translator->setFlushStrategy(Translator::FLUSH_RUNTIME);

        $translator->trans('admin.empty.translation', array(), 'trans_domain', 'fr');

        /** @var Router $urlGenerator */
        $urlGenerator = $container->get('router');

        $crawler = $client->request("GET", '/admin/translations/');

        $this->assertEquals(1, $crawler->filter('div.container .row')->count());
        $this->assertEquals(1, $crawler->filter('div.container .row h3 + ul > li')->count());

        $transLink = $crawler->filter('div.container .row h3 + ul > li a');

        $this->assertContains('trans_domain', $transLink->html());
        $this->assertEquals('(0 / 1)', $transLink->filter('span')->html());
        $this->assertEquals($urlGenerator->generate('pierstoval_translation_edit', array('locale' => 'fr', 'domain' => 'trans_domain'), UrlGenerator::ABSOLUTE_URL), $transLink->attr('href'));

        $crawler->clear();
        $crawler = $client->click($transLink->link('GET'));

        $this->assertEquals(1, $crawler->filter('.container h1 + .alert#always_save_indicator')->count());
        $this->assertEquals(1, $crawler->filter('.container h4 + h4 + form#translate_update')->count());
        $this->assertEquals(1, $crawler->filter('form#translate_update label.control-label[data-token]')->count());
        $this->assertEquals(1, $crawler->filter('form#translate_update textarea[data-modified="false"]')->count());

        $this->assertContains('var message_replace_content = ', $crawler->html());

        /** @var Form $form */
        $form = $crawler->selectButton('submit_updates')->form();

        $formTranslationElement = $form['translation'];

        /** @var TextareaFormField $formTranslationElement */
        $token = key($formTranslationElement);
        $formTranslationElement = $formTranslationElement[$token];
        $value = $formTranslationElement->getValue();
        $this->assertEmpty($value);

        $form['translation['.$token.']'] = 'translated_element';
        $crawler->clear();
        $crawler = $client->submit($form);

        $this->assertContains($translator->trans('translations_saved_count', array('%count%' => 1), 'pierstoval_translation', 'fr'), $crawler->html());

        $crawler->clear();
    }

}