<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Category;
use App\Entity\Technique;
use App\Entity\Painting;
use Faker\Factory;

class PintingFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // 👉 On récupère les entités existantes avec ID = 1
        $category = $manager->getRepository(Category::class)->find(1);
        $technique = $manager->getRepository(Technique::class)->find(1);

        // Vérification : si elles n'existent pas, on pourrait les créer
        if (!$category || !$technique) {
            throw new \Exception("Category ou Technique avec ID=1 introuvable !");
        }

        for ($i = 0; $i < 10; $i++) {
            $gallery = new Painting();
            $gallery->setTitle($faker->sentence(3));
            $gallery->setDescription($faker->paragraph());
            $gallery->setCreated(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 years', 'now')));
            $gallery->setHeight($faker->randomFloat(2, 10, 100));
            $gallery->setWidth($faker->randomFloat(2, 10, 100));
            $gallery->setImage('image_' . $i . '.jpg');
            $gallery->setVisible(1);

            $gallery->setIdCategory($category);
            $gallery->setIdTechnique($technique);

            $manager->persist($gallery);
        }

        $manager->flush();
    }
}
