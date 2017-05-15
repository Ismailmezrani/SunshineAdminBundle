import React, { Component } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { fetchMenu } from '../actions/action_menu.jsx';

import MenuSunshinePage from '../components/menu/MenuSunshinePage.jsx';

class Sidebar extends React.Component {

    componentWillMount() {
        this.props.fetchMenu();
    }

    getMenuElementByType ( element ) {
        if ( element.type == "sunshinePage") {
            return (<div><MenuSunshinePage element={element} /></div>);
        } else if ( element.type == "externalPage" ) {
            return (<div>externalPage</div>);
        } else if ( element.type == "subMenu" ) {
            return (<div>Sous menu</div>);
        } else if ( element.type == "section" ) {
            return (<div>section</div>);
        }
    }

    render() {

        if (this.props.menu == null) {console.log ('pas de menu');return (<div></div>)}

        return (
            <div className="page-sidebar-wrapper">
                <div className="page-sidebar navbar-collapse collapse">
                    <ul className="page-sidebar-menu  page-header-fixed " data-keep-expanded="false" data-auto-scroll="true" data-slide-speed="200" style={{paddingTop: '20px'}}>
                        {this.props.menu.map((menuElement, index) => {
                            return (<div key={index}>{this.getMenuElementByType( menuElement )}</div>)
                        })}
                    </ul>
                </div>
            </div>
        );
    }
}

function mapDispatchToProps(dispatch) {
    return bindActionCreators({ fetchMenu }, dispatch);
}

function mapStateToProps({ menu }) {
    return { menu };
}

export default connect(mapStateToProps, mapDispatchToProps)(Sidebar);