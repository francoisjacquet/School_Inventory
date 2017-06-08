/**
 * Install SQL
 * Required as the module adds programs to other modules
 * - Add profile exceptions for the module to appear in the menu
 * - Add module specific tables (and their eventual sequences & indexes)
 *
 * @package School Inventory module
 */

-- Fix #102 error language "plpgsql" does not exist
-- http://timmurphy.org/2011/08/27/create-language-if-it-doesnt-exist-in-postgresql/
--
-- Name: create_language_plpgsql(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION create_language_plpgsql()
RETURNS BOOLEAN AS $$
    CREATE LANGUAGE plpgsql;
    SELECT TRUE;
$$ LANGUAGE SQL;

SELECT CASE WHEN NOT (
    SELECT TRUE AS exists FROM pg_language
    WHERE lanname='plpgsql'
    UNION
    SELECT FALSE AS exists
    ORDER BY exists DESC
    LIMIT 1
) THEN
    create_language_plpgsql()
ELSE
    FALSE
END AS plpgsql_created;

DROP FUNCTION create_language_plpgsql();



/*******************************************************
 profile_id:
 	- 0: student
 	- 1: admin
 	- 2: teacher
 	- 3: parent
 modname: should match the Menu.php entries
 can_use: 'Y'
 can_edit: 'Y' or null (generally null for non admins)
*******************************************************/
--
-- Data for Name: profile_exceptions; Type: TABLE DATA;
--

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (
1, 'School_Inventory/SchoolInventory.php', 'Y', 'Y');

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (
2, 'School_Inventory/SchoolInventory.php', 'Y', NULL);


/**
 * Add module tables
 */

/**
 * Category cross item table
 */
--
-- Name: school_inventory_categoryxitem; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE school_inventory_categoryxitem (
    item_id numeric NOT NULL,
    category_id numeric NOT NULL,
    category_type character varying(255) NOT NULL
);



--
-- Name: school_inventory_categoryxitem_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE INDEX school_inventory_categoryxitem_ind ON school_inventory_categoryxitem USING btree (category_id);



/**
 * Items table
 */
--
-- Name: items; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE school_inventory_items (
    item_id numeric PRIMARY KEY,
    school_id numeric NOT NULL,
    title character varying(255) NOT NULL,
    sort_order numeric,
    type character varying(255),
    quantity numeric,
    comments text,
    file text,
    price numeric,
    "date" date
);


--
-- Name: school_inventory_items_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE school_inventory_items_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;



--
-- Name: school_inventory_items_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE INDEX school_inventory_items_ind ON school_inventory_items USING btree (school_id);



/**
 * Categories table
 */
--
-- Name: categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE school_inventory_categories (
    category_id numeric PRIMARY KEY,
    category_type character varying(255) NOT NULL,
    category_key character varying(255),
    school_id numeric NOT NULL,
    title character varying(255) NOT NULL,
    sort_order numeric,
    color character varying(255)
);


--
-- Name: school_inventory_categories_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE school_inventory_categories_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;



--
-- Name: school_inventory_categories_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE INDEX school_inventory_categories_ind ON school_inventory_categories USING btree (school_id);



--
-- Data for Name: school_inventory_categories; Type: TABLE DATA;
--

INSERT INTO school_inventory_categories (category_id, school_id, title, sort_order, category_type)
SELECT nextval('school_inventory_categories_seq'), sch.id, 'Computers', null, 'CATEGORY'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM school_inventory_categories
    WHERE title='Computers'
    AND category_type='CATEGORY');


INSERT INTO school_inventory_categories (category_id, school_id, title, sort_order, category_type)
SELECT nextval('school_inventory_categories_seq'), sch.id, 'Consumables', null, 'CATEGORY'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM school_inventory_categories
    WHERE title='Consumables'
    AND category_type='CATEGORY');


INSERT INTO school_inventory_categories (category_id, school_id, title, sort_order, category_type)
SELECT nextval('school_inventory_categories_seq'), sch.id, 'Needs repair', null, 'STATUS'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM school_inventory_categories
    WHERE title='Needs repair'
    AND category_type='STATUS');


INSERT INTO school_inventory_categories (category_id, school_id, title, sort_order, category_type)
SELECT nextval('school_inventory_categories_seq'), sch.id, 'Buy more', null, 'STATUS'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM school_inventory_categories
    WHERE title='Buy more'
    AND category_type='STATUS');


INSERT INTO school_inventory_categories (category_id, school_id, title, sort_order, category_type)
SELECT nextval('school_inventory_categories_seq'), sch.id, 'Lended', null, 'STATUS'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM school_inventory_categories
    WHERE title='Lended'
    AND category_type='STATUS');
