import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Button, Card, CardBody, Notice } from '@wordpress/components';
import BreakpointRow from './BreakpointRow';
import RangeBar from './RangeBar';
import { computeRanges } from '../lib/ranges';

const boot = window.rvAdmin || {};
const strings = boot.strings || {};

let uid = 0;
const nextId = () => `row-${ ++uid }`;

// Map the option shape (snake_case wire) -> editable row state. null -> '' for the input.
const toRows = ( list ) =>
	( Array.isArray( list ) ? list : [] ).map( ( bp ) => ( {
		_id: nextId(),
		slug: bp.slug || '',
		label: bp.label || '',
		maxWidth:
			bp.max_width === null || bp.max_width === undefined
				? ''
				: String( bp.max_width ),
	} ) );

// Map editable rows -> REST payload (snake_case wire). Drop empty-label rows; '' -> null.
const toPayload = ( rows ) =>
	rows
		.filter( ( row ) => row.label.trim() !== '' )
		.map( ( row ) => ( {
			slug: row.slug || undefined,
			label: row.label.trim(),
			max_width: row.maxWidth === '' ? null : Number( row.maxWidth ),
		} ) );

const initialRows =
	Array.isArray( boot.breakpoints ) && boot.breakpoints.length
		? boot.breakpoints
		: boot.defaults;

export default function App() {
	const [ rows, setRows ] = useState( () => toRows( initialRows ) );
	const [ busy, setBusy ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	const computed = computeRanges( rows, strings );
	const rangeByIndex = computed.reduce( ( acc, entry ) => {
		acc[ entry.index ] = entry;
		return acc;
	}, {} );
	const hasInvalid = computed.some( ( entry ) => entry.invalid );

	const updateRow = ( index, patch ) =>
		setRows( ( prev ) =>
			prev.map( ( row, i ) =>
				i === index ? { ...row, ...patch } : row
			)
		);

	const addRow = () =>
		setRows( ( prev ) => [
			...prev,
			{ _id: nextId(), slug: '', label: '', maxWidth: '1400' },
		] );

	const removeRow = ( index ) =>
		setRows( ( prev ) =>
			prev.length > 1 ? prev.filter( ( _, i ) => i !== index ) : prev
		);

	const request = async ( options, successMessage ) => {
		setBusy( true );
		setNotice( null );
		try {
			const response = await apiFetch( {
				path: boot.restPath,
				...options,
			} );
			setRows( toRows( response.breakpoints ) );
			setNotice( { status: 'success', message: successMessage } );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message: ( error && error.message ) || strings.error,
			} );
		} finally {
			setBusy( false );
		}
	};

	const save = () =>
		request(
			{ method: 'POST', data: { breakpoints: toPayload( rows ) } },
			strings.saved
		);

	const reset = () => {
		// eslint-disable-next-line no-alert
		if ( ! window.confirm( strings.resetConfirm ) ) {
			return;
		}
		request( { method: 'DELETE' }, strings.resetDone );
	};

	return (
		<div className="rv-admin">
			<h1>{ strings.title }</h1>
			<p className="rv-admin__intro">{ strings.intro }</p>

			{ notice && (
				<Notice
					status={ notice.status }
					onRemove={ () => setNotice( null ) }
				>
					{ notice.message }
				</Notice>
			) }

			<RangeBar computed={ computed } />

			<Card className="rv-admin__card">
				<CardBody>
					<table className="rv-admin__table">
						<thead>
							<tr>
								<th className="rv-col-label">
									{ strings.label }
								</th>
								<th className="rv-col-max">
									{ strings.maxWidth }
								</th>
								<th className="rv-col-range">
									{ strings.hiddenWhen }
								</th>
								<th
									className="rv-col-actions"
									aria-label="actions"
								/>
							</tr>
						</thead>
						<tbody>
							{ rows.map( ( row, index ) => (
								<BreakpointRow
									key={ row._id }
									row={ row }
									range={ rangeByIndex[ index ] }
									strings={ strings }
									canRemove={ rows.length > 1 }
									onChange={ ( patch ) =>
										updateRow( index, patch )
									}
									onRemove={ () => removeRow( index ) }
								/>
							) ) }
						</tbody>
					</table>

					<div className="rv-admin__add">
						<Button variant="secondary" onClick={ addRow }>
							{ `+ ${ strings.addRow }` }
						</Button>
					</div>
				</CardBody>
			</Card>

			{ hasInvalid && (
				<Notice status="warning" isDismissible={ false }>
					{ strings.multiBlank }
				</Notice>
			) }

			<div className="rv-admin__footer">
				<Button
					variant="link"
					isDestructive
					isBusy={ busy }
					disabled={ busy }
					onClick={ reset }
				>
					{ strings.reset }
				</Button>
				<Button
					variant="primary"
					isBusy={ busy }
					disabled={ busy || hasInvalid }
					onClick={ save }
				>
					{ strings.save }
				</Button>
			</div>
		</div>
	);
}
