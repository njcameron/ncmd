<?php

/* core/themes/seven/templates/page.html.twig */
class __TwigTemplate_03ddcc178a9f4ccbb464359d5edd60daa0936de4f4850a59f6af45976ef76fe0 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 60
        echo "  <header class=\"content-header clearfix\">
    <div class=\"layout-container\">
      ";
        // line 62
        echo $this->env->getExtension('drupal_core')->escapeFilter($this->env, (isset($context["title_prefix"]) ? $context["title_prefix"] : null), "html", null, true);
        echo "
      ";
        // line 63
        if ((isset($context["title"]) ? $context["title"] : null)) {
            // line 64
            echo "        <h1 class=\"page-title\">";
            echo $this->env->getExtension('drupal_core')->escapeFilter($this->env, (isset($context["title"]) ? $context["title"] : null), "html", null, true);
            echo "</h1>
      ";
        }
        // line 66
        echo "      ";
        echo $this->env->getExtension('drupal_core')->escapeFilter($this->env, (isset($context["title_suffix"]) ? $context["title_suffix"] : null), "html", null, true);
        echo "
      ";
        // line 67
        if ((isset($context["primary_local_tasks"]) ? $context["primary_local_tasks"] : null)) {
            // line 68
            echo "        ";
            echo $this->env->getExtension('drupal_core')->escapeFilter($this->env, (isset($context["primary_local_tasks"]) ? $context["primary_local_tasks"] : null), "html", null, true);
            echo "
      ";
        }
        // line 70
        echo "    </div>
  </header>

  <div class=\"layout-container\">
    ";
        // line 74
        if ((isset($context["secondary_local_tasks"]) ? $context["secondary_local_tasks"] : null)) {
            // line 75
            echo "      <div class=\"tabs-secondary clearfix\" role=\"navigation\">";
            echo $this->env->getExtension('drupal_core')->escapeFilter($this->env, (isset($context["secondary_local_tasks"]) ? $context["secondary_local_tasks"] : null), "html", null, true);
            echo "</div>
    ";
        }
        // line 77
        echo "
    ";
        // line 78
        echo $this->env->getExtension('drupal_core')->escapeFilter($this->env, $this->getAttribute((isset($context["page"]) ? $context["page"] : null), "breadcrumb", array()), "html", null, true);
        echo "

    <main class=\"page-content clearfix\" role=\"main\">
      <div class=\"visually-hidden\"><a id=\"main-content\" tabindex=\"-1\"></a></div>
      ";
        // line 82
        echo $this->env->getExtension('drupal_core')->escapeFilter($this->env, $this->getAttribute((isset($context["page"]) ? $context["page"] : null), "messages", array()), "html", null, true);
        echo "
      ";
        // line 83
        if ($this->getAttribute((isset($context["page"]) ? $context["page"] : null), "help", array())) {
            // line 84
            echo "        <div class=\"help\">
          ";
            // line 85
            echo $this->env->getExtension('drupal_core')->escapeFilter($this->env, $this->getAttribute((isset($context["page"]) ? $context["page"] : null), "help", array()), "html", null, true);
            echo "
        </div>
      ";
        }
        // line 88
        echo "      ";
        if ((isset($context["action_links"]) ? $context["action_links"] : null)) {
            // line 89
            echo "        <ul class=\"action-links\">
          ";
            // line 90
            echo $this->env->getExtension('drupal_core')->escapeFilter($this->env, (isset($context["action_links"]) ? $context["action_links"] : null), "html", null, true);
            echo "
        </ul>
      ";
        }
        // line 93
        echo "      ";
        echo $this->env->getExtension('drupal_core')->escapeFilter($this->env, $this->getAttribute((isset($context["page"]) ? $context["page"] : null), "content", array()), "html", null, true);
        echo "
    </main>

  </div>
";
    }

    public function getTemplateName()
    {
        return "core/themes/seven/templates/page.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  99 => 93,  93 => 90,  90 => 89,  87 => 88,  81 => 85,  78 => 84,  76 => 83,  72 => 82,  65 => 78,  62 => 77,  56 => 75,  54 => 74,  48 => 70,  42 => 68,  40 => 67,  35 => 66,  29 => 64,  27 => 63,  23 => 62,  19 => 60,);
    }
}
