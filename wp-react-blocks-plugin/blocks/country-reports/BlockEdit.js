import {InspectorControls, useBlockProps} from '@wordpress/block-editor';
import {Panel, PanelBody, PanelRow, SelectControl, TextControl, RangeControl} from '@wordpress/components';
import {__} from '@wordpress/i18n';
import {BaseBlockEdit} from '../commons/index'

class BlockEdit extends BaseBlockEdit {

    render() {
        const {
            className, isSelected, toggleSelection, setAttributes, attributes: {
                description, country, year, image, width, height
            }
        } = this.props;

        let queryString = `data-description=${description}`;
        queryString += `&data-country=${country}`;
        queryString += `&data-year=${year}`;
        queryString += `&data-image=${image}`
        queryString += `&data-height=${height}`
        queryString += `&editing=true`;
        const divStyles = {height: height + 'px', width: width + 'px'}
        return ([isSelected && (<InspectorControls>
            <Panel header={__("Country Report Configuration")}>
                <PanelBody>
                    <PanelRow>
                        {this.getCategoryValues('country-report', 'Countries', country, 'country')}
                    </PanelRow>
                    <PanelRow>
                        {this.getCategoryValues('years', 'Years', year, 'year')}
                    </PanelRow>
                    <PanelRow>
                        {this.getImages(image)}
                    </PanelRow>
                    <PanelRow>
                        <TextControl
                            label={__('Description:')}
                            value={description}
                            onChange={(description) => setAttributes({description})}
                        />
                    </PanelRow>
                    <PanelRow>
                        <RangeControl
                            label={__('Chart Width')}
                            value={width}
                            onChange={(width) => setAttributes({width})}
                            min={1}
                            max={1000}
                        />
                    </PanelRow>
                    <PanelRow>
                        <RangeControl
                            label={__('Chart height')}
                            value={height}
                            onChange={(height) => setAttributes({height})}
                            min={1}
                            max={1000}
                        />
                    </PanelRow>
                </PanelBody>
            </Panel>
        </InspectorControls>), (<div>
            <iframe id={"id_country_reports_iframe"} scrolling={"no"}
                    style={divStyles}
                    src={this.state.react_ui_url + "/en/embeddable/countryReports?" + queryString}/>
        </div>)]);
    }

    getImages = (image) => {
        const list = [];
        list.push({label: '', value: ''});
        return (<SelectControl
            label={__('Images')}
            value={image}
            onChange={(value) => {
                setAttributes({image: value})
            }}
            options={list}/>);
    }

    getCategoryValues = (category, title, val, key) => {
        const {setAttributes} = this.props;
        const {categories} = this.state;
        let list = [];
        list.push({label: '', value: ''});
        if (categories) {
            const parent = categories.find(i => i.slug === category);
            categories.filter(i => i.parent === parent.id)
                .sort(i => i.name.toLowerCase())
                .map(i => {
                    return {label: i.name, value: i.id};
                }).forEach(i => {
                list.push(i);
            });
        }
        return (<SelectControl
            label={__(title)}
            value={val} // e.g: value = [ 'a', 'c' ]
            onChange={(value) => {
                setAttributes({[key]: value})
            }}
            options={list}
        />)
    }
}

const Edit = (props) => {
    const blockProps = useBlockProps({className: 'wp-react-component'});
    return <div {...blockProps}><BlockEdit {...props} /></div>;
}

export default Edit;
