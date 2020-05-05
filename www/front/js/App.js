var React = require('react');

class App extends React.Component {
    constructor(props) {
        super(props);
        this.state = {activeTab: 'status'};
        this.changeTab = this.changeTab.bind(this);
    }
    render() {
        return <div>
            <h1>Awesome vault monitor</h1>
            <MainNav activeTab={this.state.activeTab} changeTab={this.changeTab}/>
            <MonitorTab activeTab={this.state.activeTab} changeTab={this.changeTab}/>
        </div>;
    }
    changeTab(tab) {
        this.setState({activeTab: tab});
    }
}

class MainNav extends React.Component {
    render() {
        return <nav className="nav">
            <MainNavLink tab="status" activeTab={this.props.activeTab} changeTab={this.props.changeTab} label="Status"/>
            <MainNavLink tab="integrity" activeTab={this.props.activeTab} changeTab={this.props.changeTab} label="Integrity"/>
            <MainNavLink tab="disk-check" activeTab={this.props.activeTab} changeTab={this.props.changeTab} label="Disk check"/>
        </nav>;
    }
}

class MainNavLink extends React.Component {
    constructor(props) {
        super(props)
        this.onTabChanged = this.onTabChanged.bind(this);
    }

    render() {
        var cls="nav-link";
        if (this.props.activeTab==this.props.tab) {
            cls=cls+" active";
        }
        return <a className={cls} href="#" onClick={this.onTabChanged}>{this.props.label}</a>;
    }

    onTabChanged(e) {
        this.props.changeTab(this.props.tab);
    }
}

class MonitorTab extends React.Component {
    render() {
        if (this.props.activeTab == 'status') {
            return <StatusTab/>;
        }
        else if (this.props.activeTab == 'integrity') {
            return <div className="container-fluid">
                <div className="row">
                    <div className="col">
                        Integrity
                    </div>
                </div>
            </div>;
        }
    }
}

class StatusTab extends React.Component {
    constructor(props) {
        super(props);
        this.state = {count: 1};
    }
    componentDidMount() {
        console.log("Status mounted");
        this.intId = setInterval((() => {
            this.setState({count: this.state.count+1});
        }), 1000);
    }
    componentDidUpdate() {
        console.log("Status updated");
    }
    componentWillUnmount() {
        console.log("Status unmountes");
        clearInterval(this.intId);
    }
    render() {
        return <div className="container-fluid">
            <div className="row">
                <div className="col">
                    Status
                    <p>{this.state.count}</p>
                </div>
            </div>
        </div>;
    }
}

module.exports = {
    App: App
};