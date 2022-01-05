import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Panel, PanelBody, PanelRow, SelectControl, TextControl, CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BaseBlockEdit } from '../commons/index'

class BlockEdit extends BaseBlockEdit {

  render() {
    const {
      className, isSelected,
      toggleSelection, setAttributes, attributes: {
        type, selectedCountryFirst, addYear, selectedCountryLabel
      }
    } = this.props;

    let queryString = `data-type=${type}`;
    queryString += `&data-selected-country-first=${selectedCountryFirst}`;
    queryString += `&data-add-year=${addYear}`;
    queryString += `&data-selected-country-label=${selectedCountryLabel}`;
    queryString += `&editing=true`
    const divStyles = {}
    return ([isSelected && (<InspectorControls>
        <Panel header={__("Filters Configuration")}>
          <PanelBody>
            <PanelRow>

              <SelectControl
                label={__('Type:')}
                value={[type]} // e.g: value = [ 'a', 'c' ]
                onChange={(value) => {
                  setAttributes({ type: value })
                }}
                options={[
                  { label: 'Country', value: 'Country' },
                  { label: 'Carousel', value: 'Carousel' }
                ]}
              />
            </PanelRow>
            <PanelRow>
              <CheckboxControl
                label={__('Selected country first:')}
                checked={selectedCountryFirst}
                onChange={() => setAttributes({ selectedCountryFirst: !selectedCountryFirst })} />
            </PanelRow>
            <PanelRow>
              <CheckboxControl
                label={__('Add year:')}
                checked={addYear}
                onChange={() => setAttributes({ addYear: !addYear })} />
            </PanelRow>
            <PanelRow>
              <TextControl
                label={__('Selected country label')}
                value={selectedCountryLabel}
                onChange={(selectedCountryLabel) => setAttributes({ selectedCountryLabel })}
              /></PanelRow>
          </PanelBody>
        </Panel>
      </InspectorControls>),

        (<div>
            <iframe id={"id_description_iframe"}  scrolling={"no"}
                    style={divStyles} src={this.state.react_ui_url + "/en/embeddable/filter?" + queryString} />
          </div>

        )]
    );

  }
}


const Edit = (props) => {

  const blockProps = useBlockProps({ className: 'wp-react-component' });
  return <div {...blockProps}><BlockEdit {...props} /></div>;


}
export default Edit;