<div class="parallax-container-image">

    <?php if($this->editmode) { ?>
        <div class="editmode-parallax-container-image">
            <?= $this->image('parallaxContainerImage', [

                'thumbnail' => [
                    'width' => 200,
                    'height' => 100,
                    'interlace' => true,
                    'quality' => 90
                ],
                'width' => 200,
                'height' => 100,
                'reload'    => true,
                'class'     => 'img-responsive',
                'dropClass' => 'canvas',
                'title'     => 'Bild hierherziehen'
            ]);
            ?>
        </div>
    <?php } ?>

    <div class="content">
        <?= $this->template('helper/areablock.php', [ 'name' => 'parallaxContainerContent', 'type' => 'image' ] ); ?>
    </div>

    <div class="background">

        <?php $thumbnail = $this->image('parallaxContainerImage')->getThumbnail('parallaxContainerImage'); ?>
        <div
            class="canvas"
            data-natural-width="<?= $this->image('parallaxContainerImage')->getThumbnail('parallaxContainerImage')->getWidth(); ?>"
            data-natural-height="<?= $this->image('parallaxContainerImage')->getThumbnail('parallaxContainerImage')->getHeight(); ?>"
            data-image-src="<?= $thumbnail ?>"
            <?= $this->select('parallaxContainerAdditionalClasses')->getData() === 'window-full-height' ? 'style="background-image:url('. $thumbnail . ');"' : '' ?>>
        </div>

    </div>

</div>