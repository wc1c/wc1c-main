<?php defined('ABSPATH') || exit;

use Wc1c\Main\Admin\Wizards\SetupWizard;

if(!isset($args['wizard']))
{
    return;
}

/** @var SetupWizard $wizard */
$wizard = $args['wizard'];

?>

<div id="wc1c-wizards" class="wc1c-wizards theme-arrows justified">
    <ul class="nav">
    <?php
        $step_i = 1;
        $steps = $wizard->getSteps();

        foreach($steps as $step => $step_data)
        {
            $classes =
            [
                'nav-link',
	            'inactive',
            ];

	        if(array_search($wizard->getStep(), array_keys($steps), true) > array_search($step, array_keys($steps), true))
	        {
		        $classes[] = 'done';
	        }

            if($wizard->getStep() === $step)
            {
	            $classes[] = 'active';
            }

            echo '<li class="nav-item">';
            //echo '<a class="'. implode(' ', $classes) .'" href="'. $wizard->getLinkByStep($step) .'">';
	        echo wp_kses_post('<a class="'. implode(' ', $classes) .'" href="#">');
            echo wp_kses_post(__('Step', 'wc1c-main') . ' ' . absint($step_i));

            if(isset($step_data['name']))
            {
                echo '<div>' . esc_html($step_data['name']) . '</div>';
            }

            echo '</a></li>';

	        $step_i++;
        }
    ?>
	</ul>

	<div class="wizard-content">
        <?php do_action('wc1c_wizard_content_output'); ?>
	</div>
</div>