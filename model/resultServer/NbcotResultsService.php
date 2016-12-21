<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\nbcot\model\resultServer;

use oat\taoResultServer\models\classes\CrudResultsService;
use oat\taoResultServer\models\classes\QtiResultsService;
use oat\taoResultServer\models\classes\ResultServerService;
use qtism\data\AssessmentItemRef;
use qtism\common\enums\Cardinality;

class NbcotResultsService extends QtiResultsService
{
    public function getQtiResultXml($deliveryId, $resultId)
    {
        $delivery = new \core_kernel_classes_Resource($deliveryId);
        $resultService = $this->getServiceLocator()->get(ResultServerService::SERVICE_ID);
        $resultServer = $resultService->getResultStorage($deliveryId);

        $crudService = new CrudResultsService();

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $itemResults = $crudService->format($resultServer, $resultId, CrudResultsService::GROUP_BY_ITEM);
        $testResults = $crudService->format($resultServer, $resultId, CrudResultsService::GROUP_BY_TEST);

        $assessmentResultElt = $dom->createElementNS(self::QTI_NS, 'assessmentResult');
        $dom->appendChild($assessmentResultElt);

        /** Context */
        $contextElt = $dom->createElementNS(self::QTI_NS, 'context');
        $contextElt->setAttribute('sourcedId', \tao_helpers_Uri::getUniqueId($resultServer->getTestTaker($resultId)));
        $assessmentResultElt->appendChild($contextElt);

        $service = \taoQtiTest_models_classes_QtiTestService::singleton();
        $testProperty = new \core_kernel_classes_Property('http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDeliveryOrigin');
        $test = new \core_kernel_classes_Resource($delivery->getOnePropertyValue($testProperty));
        $xml = $service->getDoc($test);

        /** Test Result */
        foreach ($testResults as $testResultIdentifier => $testResult) {
            $identifierParts = explode('.', $testResultIdentifier);
            $testIdentifier = array_pop($identifierParts);

            $testResultElement = $dom->createElementNS(self::QTI_NS, 'testResult');
            $testResultElement->setAttribute('identifier', $testIdentifier);
            $testResultElement->setAttribute('datestamp', \tao_helpers_Date::displayeDate(
                $testResult[0]['epoch'],
                \tao_helpers_Date::FORMAT_ISO8601
            ));

            /** Item Variable */
            foreach ($testResult as $itemVariable) {

                $isResponseVariable = $itemVariable['type']->getUri() === 'http://www.tao.lu/Ontologies/TAOResult.rdf#ResponseVariable';
                $testVariableElement = $dom->createElementNS(self::QTI_NS, ($isResponseVariable) ? 'responseVariable' : 'outcomeVariable');
                $testVariableElement->setAttribute('identifier', $itemVariable['identifier']);
                $testVariableElement->setAttribute('cardinality', $itemVariable['cardinality']);
                $testVariableElement->setAttribute('baseType', $itemVariable['basetype']);

                $valueElement = $this->createCDATANode($dom, 'value', trim($itemVariable['value']));

                if ($isResponseVariable) {
                    $candidateResponseElement = $dom->createElementNS(self::QTI_NS, 'candidateResponse');
                    $candidateResponseElement->appendChild($valueElement);
                    $testVariableElement->appendChild($candidateResponseElement);
                } else {
                    $testVariableElement->appendChild($valueElement);
                }

                $testResultElement->appendChild($testVariableElement);
            }

            $assessmentResultElt->appendChild($testResultElement);
        }

        /** Item Result */
        foreach ($itemResults as $itemResultIdentifier => $itemResult) {

            // Retrieve identifier.
            $identifierParts = explode('.', $itemResultIdentifier);
            $occurenceNumber = array_pop($identifierParts);
            $refIdentifier = array_pop($identifierParts);

            /** @var AssessmentItemRef $item */
            $item = $xml->getDocumentComponent()->getComponentByIdentifier($refIdentifier);
            $rdfItem = new \core_kernel_classes_Resource($item->getHref());
            $nbcotIdentifier = $rdfItem->getLabel() . '_' . $refIdentifier;

            $itemElement = $dom->createElementNS(self::QTI_NS, 'itemResult');
            $itemElement->setAttribute('identifier', $nbcotIdentifier);
            $itemElement->setAttribute('datestamp', \tao_helpers_Date::displayeDate(
                $itemResult[0]['epoch'],
                \tao_helpers_Date::FORMAT_ISO8601
            ));
            $itemElement->setAttribute('sessionStatus', 'final');

            /** Item variables */
            foreach ($itemResult as $key => $itemVariable) {
                $isResponseVariable = $itemVariable['type']->getUri() === 'http://www.tao.lu/Ontologies/TAOResult.rdf#ResponseVariable';

                if ($itemVariable['identifier']=='comment') {
                    /** Comment */
                    $itemVariableElement = $dom->createElementNS(self::QTI_NS, 'candidateComment', $itemVariable['value']);
                } else {
                    /** Item variable */
                    $itemVariableElement = $dom->createElementNS(self::QTI_NS, ($isResponseVariable) ? 'responseVariable' : 'outcomeVariable');
                    $itemVariableElement->setAttribute('identifier', $itemVariable['identifier']);
                    $itemVariableElement->setAttribute('cardinality', $itemVariable['cardinality']);
                    $itemVariableElement->setAttribute('baseType', $itemVariable['basetype']);

                    /** Split multiple response */
                    $itemVariable['value'] = trim($itemVariable['value'], '[]');
                    if ($itemVariable['cardinality']!==Cardinality::getNameByConstant(Cardinality::SINGLE)) {
                        $values = explode(';', $itemVariable['value']);
                        $returnValue = [];
                        foreach ($values as $value) {
                            $returnValue[] = $this->createCDATANode($dom, 'value', $value);
                        }
                    } else {
                        $returnValue = $this->createCDATANode($dom, 'value', $itemVariable['value']);
                    }

                    /** Get response parent element */
                    if ($isResponseVariable) {
                        /** Response variable */
                        $responseElement = $dom->createElementNS(self::QTI_NS, 'candidateResponse');
                    } else {
                        /** Outcome variable */
                        $responseElement = $itemVariableElement;
                    }

                    /** Write a response node foreach answer  */
                    if (is_array($returnValue)) {
                        foreach ($returnValue as $valueElement) {
                            $responseElement->appendChild($valueElement);
                        }
                    } else {
                        $responseElement->appendChild($returnValue);
                    }

                    if ($isResponseVariable) {
                        $itemVariableElement->appendChild($responseElement);
                    }
                }

                $itemElement->appendChild($itemVariableElement);
            }

            $assessmentResultElt->appendChild($itemElement);
        }

        return $dom->saveXML();
    }

}