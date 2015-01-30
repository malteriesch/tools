<?php
namespace UnitTest\Lib\Traits;

trait XmlAssertions {

    protected function _createTextXmlElement() {
        return new \SimpleXMLElement("<test></test>");
    }
    protected function assertTestXmlElement($expectedXmlString,$xmlNode){
        $this->assertXmlStringEqualsXmlString(
                "<test>
                $expectedXmlString
                </test>",$xmlNode->asXML());
    }

    protected function assertTestXmlElementsWithAttributes($expectedXmlString,$expectedAttributesString,$xmlNode){
        $this->assertXmlStringEqualsXmlString(
            "<test $expectedAttributesString>
            $expectedXmlString
            </test>",$xmlNode->asXML());
    }

}