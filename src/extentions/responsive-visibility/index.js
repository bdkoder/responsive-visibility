import "./editor.scss";
import "./style.scss";
import resposiveVisibilityBlockAttributes from "./components/attributes";
import withBlockWrapperClass from "./components/block-wrapper";
import withResponsiveVisibilityControls from "./components/settings";
import { addFilter } from "@wordpress/hooks";

// Add Inspector Controls
addFilter(
	"editor.BlockEdit",
	"responsive-visibility/responsive-visibility/with-inspector-controls",
	withResponsiveVisibilityControls,
);

// Add Block attributes
addFilter(
	"blocks.registerBlockType",
	"responsive-visibility/responsive-visibility/with-block-attributes",
	resposiveVisibilityBlockAttributes,
);

// Add Block wrapper class
addFilter(
	"editor.BlockListBlock",
	"responsive-visibility/responsive-visibility/with-block-wrapper-class",
	withBlockWrapperClass,
);
