<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

/**
 * @ORM\Table(
 *      name="oro_workflow_definition", indexes={
 *          @ORM\Index(name="oro_workflow_definition_enabled_idx", columns={"enabled"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class WorkflowDefinition
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="string", length=255, unique=true)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $label;

    /**
     * @var string
     *
     * @ORM\Column(name="related_entity", type="string", length=255)
     */
    protected $relatedEntity;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_attribute_name", type="string", length=255)
     */
    protected $entityAttributeName;

    /**
     * @var bool
     * @ORM\Column(name="steps_display_ordered", type="boolean")
     */
    protected $stepsDisplayOrdered = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $enabled;

    /**
     * @var array
     *
     * @ORM\Column(name="configuration", type="array")
     */
    protected $configuration;

    /**
     * @var WorkflowStep[]|Collection
     *
     * @ORM\OneToMany(
     *      targetEntity="WorkflowStep",
     *      mappedBy="definition",
     *      orphanRemoval=true,
     *      cascade={"all"}
     * )
     */
    protected $steps;

    /**
     * @var WorkflowStep
     *
     * @ORM\ManyToOne(targetEntity="WorkflowStep")
     * @ORM\JoinColumn(name="start_step_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $startStep;

    /**
     * @var WorkflowEntityAcl[]|Collection
     *
     * @ORM\OneToMany(
     *      targetEntity="WorkflowEntityAcl",
     *      mappedBy="definition",
     *      orphanRemoval=true,
     *      cascade={"all"}
     * )
     */
    protected $entityAcls;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->enabled = false;
        $this->configuration = array();
        $this->steps = new ArrayCollection();
        $this->entityAcls = new ArrayCollection();
    }

    /**
     * Set name
     *
     * @param string $name
     * @return WorkflowDefinition
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set label
     *
     * @param string $label
     * @return WorkflowDefinition
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $relatedEntity
     * @return WorkflowDefinition
     */
    public function setRelatedEntity($relatedEntity)
    {
        $this->relatedEntity = $relatedEntity;

        return $this;
    }

    /**
     * @return string
     */
    public function getRelatedEntity()
    {
        return $this->relatedEntity;
    }

    /**
     * @param string $entityAttributeName
     * @return WorkflowDefinition
     */
    public function setEntityAttributeName($entityAttributeName)
    {
        $this->entityAttributeName = $entityAttributeName;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntityAttributeName()
    {
        return $this->entityAttributeName;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     * @return WorkflowDefinition
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool)$enabled;

        return $this;
    }

    /**
     * Is enabled
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return boolean
     */
    public function isStepsDisplayOrdered()
    {
        return $this->stepsDisplayOrdered;
    }

    /**
     * @param boolean $stepsDisplayOrdered
     * @return WorkflowDefinition
     */
    public function setStepsDisplayOrdered($stepsDisplayOrdered)
    {
        $this->stepsDisplayOrdered = $stepsDisplayOrdered;

        return $this;
    }

    /**
     * Set configuration
     *
     * @param array $configuration
     * @return WorkflowDefinition
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;

        return $this;
    }

    /**
     * Get configuration
     *
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param WorkflowStep $startStep
     * @return WorkflowDefinition
     * @throws WorkflowException
     */
    public function setStartStep($startStep)
    {
        if (null !== $startStep) {
            $stepName = $startStep->getName();

            if (!$this->hasStepByName($stepName)) {
                throw new WorkflowException(
                    sprintf('Workflow "%s" does not contain step "%s"', $this->getName(), $stepName)
                );
            }

            $this->startStep = $this->getStepByName($stepName);
        } else {
            $this->startStep = null;
        }

        return $this;
    }

    /**
     * @return WorkflowStep
     */
    public function getStartStep()
    {
        return $this->startStep;
    }

    /**
     * @return WorkflowStep[]
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * @param WorkflowStep[]|Collection $steps
     * @return WorkflowDefinition
     */
    public function setSteps($steps)
    {
        $newStepNames = array();
        foreach ($steps as $step) {
            $newStepNames[] = $step->getName();
        }

        foreach ($this->steps as $step) {
            if (!in_array($step->getName(), $newStepNames)) {
                $this->removeStep($step);
            }
        }

        foreach ($steps as $step) {
            $this->addStep($step);
        }

        return $this;
    }

    /**
     * @param WorkflowStep $step
     * @return WorkflowDefinition
     */
    public function addStep(WorkflowStep $step)
    {
        $stepName = $step->getName();

        if (!$this->hasStepByName($stepName)) {
            $step->setDefinition($this);
            $this->steps->add($step);
        } else {
            $this->getStepByName($stepName)->import($step);
        }

        return $this;
    }

    /**
     * @param WorkflowStep $step
     * @return WorkflowDefinition
     */
    public function removeStep(WorkflowStep $step)
    {
        $stepName = $step->getName();

        if ($this->hasStepByName($stepName)) {
            $step = $this->getStepByName($stepName);
            $this->steps->removeElement($step);
        }

        return $this;
    }

    /**
     * @param string $stepName
     * @return bool
     */
    public function hasStepByName($stepName)
    {
        return $this->getStepByName($stepName) !== null;
    }

    /**
     * @param string $stepName
     * @return null|WorkflowStep
     */
    public function getStepByName($stepName)
    {
        foreach ($this->steps as $step) {
            if ($step->getName() == $stepName) {
                return $step;
            }
        }

        return null;
    }

    /**
     * @return WorkflowEntityAcl[]|Collection
     */
    public function getEntityAcls()
    {
        return $this->entityAcls;
    }

    /**
     * @param WorkflowEntityAcl[]|Collection $entityAcl
     * @return WorkflowDefinition
     */
    public function setEntityAcls($entityAcl)
    {
        $newAttributeSteps = array();
        foreach ($entityAcl as $acl) {
            $newAttributeSteps[] = $acl->getAttributeStepKey();
        }

        foreach ($this->entityAcls as $acl) {
            if (!in_array($acl->getAttributeStepKey(), $newAttributeSteps)) {
                $this->removeEntityAcl($acl);
            }
        }

        foreach ($entityAcl as $acl) {
            $this->addEntityAcl($acl);
        }

        return $this;
    }

    /**
     * @param WorkflowEntityAcl $acl
     * @return WorkflowDefinition
     */
    public function addEntityAcl(WorkflowEntityAcl $acl)
    {
        $attributeStep = $acl->getAttributeStepKey();

        if (!$this->hasEntityAclByAttributeStep($attributeStep)) {
            $acl->setDefinition($this)
                ->setStep($this->getStepByName($acl->getStep()->getName()));
            $this->entityAcls->add($acl);
        } else {
            $this->getEntityAclByAttributeStep($attributeStep)->import($acl);
        }

        return $this;
    }

    /**
     * @param WorkflowEntityAcl $acl
     * @return WorkflowDefinition
     */
    public function removeEntityAcl(WorkflowEntityAcl $acl)
    {
        $attributeStep = $acl->getAttributeStepKey();

        if ($this->hasEntityAclByAttributeStep($attributeStep)) {
            $acl = $this->getEntityAclByAttributeStep($attributeStep);
            $this->entityAcls->removeElement($acl);
        }

        return $this;
    }

    /**
     * @param string $attributeStep
     * @return bool
     */
    public function hasEntityAclByAttributeStep($attributeStep)
    {
        return $this->getEntityAclByAttributeStep($attributeStep) !== null;
    }

    /**
     * @param string $attributeStep
     * @return null|WorkflowEntityAcl
     */
    public function getEntityAclByAttributeStep($attributeStep)
    {
        foreach ($this->entityAcls as $acl) {
            if ($acl->getAttributeStepKey() == $attributeStep) {
                return $acl;
            }
        }

        return null;
    }

    /**
     * @param WorkflowDefinition $definition
     * @return WorkflowDefinition
     */
    public function import(WorkflowDefinition $definition)
    {
        // enabled flag should not be imported
        $this->setName($definition->getName())
            ->setLabel($definition->getLabel())
            ->setRelatedEntity($definition->getRelatedEntity())
            ->setEntityAttributeName($definition->getEntityAttributeName())
            ->setConfiguration($definition->getConfiguration())
            ->setSteps($definition->getSteps())
            ->setStartStep($definition->getStartStep())
            ->setStepsDisplayOrdered($definition->isStepsDisplayOrdered())
            ->setEntityAcls($definition->getEntityAcls());

        return $this;
    }
}
