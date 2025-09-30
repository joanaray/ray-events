<?php
get_header();
if (have_posts()):
    while (have_posts()):
        the_post();
        $data = get_field('data_evento');
        $local = get_field('local_evento');
        $organizacao = get_field('organizador_evento'); ?>
        <section>
            <div>
                <?php if (has_post_thumbnail()) { ?>
                    <div>
                        <figure>
                            <?php the_post_thumbnail('medium'); ?>
                        </figure>
                    </div>
                <?php } ?>
                <?php esc_html(the_title('<h1>', '</h1>')); ?>
            </div>
            <div>
                <?php
                if ($data || $local || $organizacao) {
                    ?>
                    <div class="d-flex gap-3 align-items-baseline mb-3">
                        <h2 class="m-0 h5"><?php _e('Sobre este evento', 'ray_events') ?></h2>
                        <ul class="d-flex p-0 list-unstyled gap-3 m-0">
                            <?php if (!empty($data)) { ?>
                                <li><?php _e('Date: ', 'ray_events');
                                echo $data; ?></li><?php } ?>
                            <?php if (!empty($local)) { ?>
                                <li><?php _e('Location: ', 'ray_events');
                                echo $local; ?></li><?php } ?>
                            <?php if (!empty($organizacao)) { ?>
                                <li><?php _e('Organiser: ', 'ray_events');
                                echo $organizacao ?></li><?php } ?>
                        </ul>
                    </div>
                <?php } ?>
            </div>
        </section>
        <?php the_content(); ?>
    <?php endwhile;
endif;
get_footer();
?>