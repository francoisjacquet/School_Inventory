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

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'School_Inventory/SchoolInventory.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='School_Inventory/SchoolInventory.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'School_Inventory/SchoolInventory.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='School_Inventory/SchoolInventory.php'
    AND profile_id=2);


/**
 * Add module tables
 */

/**
 * Category cross item table
 */
--
-- Name: school_inventory_categoryxitem; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_school_inventory_categoryxitem() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA
        AND tablename = 'school_inventory_categoryxitem') THEN
    RAISE NOTICE 'Table "school_inventory_categoryxitem" already exists.';
    ELSE
        CREATE TABLE school_inventory_categoryxitem (
            item_id numeric NOT NULL,
            category_id numeric NOT NULL,
            category_type character varying(255) NOT NULL
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_school_inventory_categoryxitem();
DROP FUNCTION create_table_school_inventory_categoryxitem();



--
-- Name: school_inventory_categoryxitem_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_school_inventory_categoryxitem_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='school_inventory_categoryxitem_ind'
        AND n.nspname=CURRENT_SCHEMA
    ) THEN
        CREATE INDEX school_inventory_categoryxitem_ind ON school_inventory_categoryxitem (category_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_school_inventory_categoryxitem_ind();
DROP FUNCTION create_index_school_inventory_categoryxitem_ind();



/**
 * Items table
 */
--
-- Name: items; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_school_inventory_items() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA
        AND tablename = 'school_inventory_items') THEN
    RAISE NOTICE 'Table "school_inventory_items" already exists.';
    ELSE
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
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_school_inventory_items();
DROP FUNCTION create_table_school_inventory_items();


--
-- Name: school_inventory_items_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE OR REPLACE FUNCTION create_sequence_school_inventory_items_seq() RETURNS void AS
$func$
BEGIN
    CREATE SEQUENCE school_inventory_items_seq
        START WITH 1
        INCREMENT BY 1
        NO MINVALUE
        NO MAXVALUE
        CACHE 1;
EXCEPTION WHEN duplicate_table THEN
    RAISE NOTICE 'Sequence "school_inventory_items_seq" already exists.';
END
$func$ LANGUAGE plpgsql;

SELECT create_sequence_school_inventory_items_seq();
DROP FUNCTION create_sequence_school_inventory_items_seq();



--
-- Name: school_inventory_items_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_school_inventory_items_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='school_inventory_items_ind'
        AND n.nspname=CURRENT_SCHEMA
    ) THEN
        CREATE INDEX school_inventory_items_ind ON school_inventory_items (school_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_school_inventory_items_ind();
DROP FUNCTION create_index_school_inventory_items_ind();



/**
 * Categories table
 */
--
-- Name: categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_school_inventory_categories() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA
        AND tablename = 'school_inventory_categories') THEN
    RAISE NOTICE 'Table "school_inventory_categories" already exists.';
    ELSE
        CREATE TABLE school_inventory_categories (
            category_id numeric PRIMARY KEY,
            category_type character varying(255) NOT NULL,
            category_key character varying(255),
            school_id numeric NOT NULL,
            title character varying(255) NOT NULL,
            sort_order numeric,
            color character varying(255)
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_school_inventory_categories();
DROP FUNCTION create_table_school_inventory_categories();



--
-- Name: school_inventory_categories_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE OR REPLACE FUNCTION create_sequence_school_inventory_categories_seq() RETURNS void AS
$func$
BEGIN
    CREATE SEQUENCE school_inventory_categories_seq
        START WITH 1
        INCREMENT BY 1
        NO MINVALUE
        NO MAXVALUE
        CACHE 1;
EXCEPTION WHEN duplicate_table THEN
    RAISE NOTICE 'Sequence "school_inventory_categories_seq" already exists.';
END
$func$ LANGUAGE plpgsql;

SELECT create_sequence_school_inventory_categories_seq();
DROP FUNCTION create_sequence_school_inventory_categories_seq();



--
-- Name: school_inventory_categories_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_school_inventory_categories_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='school_inventory_categories_ind'
        AND n.nspname=CURRENT_SCHEMA
    ) THEN
        CREATE INDEX school_inventory_categories_ind ON school_inventory_categories (school_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_school_inventory_categories_ind();
DROP FUNCTION create_index_school_inventory_categories_ind();



--
-- Data for Name: school_inventory_categories; Type: TABLE DATA;
--

INSERT INTO school_inventory_categories (category_id, school_id, title, sort_order, category_type)
SELECT nextval('school_inventory_categories_seq'), sch.id, 'Computers', null, 'CATEGORY'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM school_inventory_categories
    WHERE title='Computers'
    AND category_type='CATEGORY')
GROUP BY sch.id;


INSERT INTO school_inventory_categories (category_id, school_id, title, sort_order, category_type)
SELECT nextval('school_inventory_categories_seq'), sch.id, 'Consumables', null, 'CATEGORY'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM school_inventory_categories
    WHERE title='Consumables'
    AND category_type='CATEGORY')
GROUP BY sch.id;


INSERT INTO school_inventory_categories (category_id, school_id, title, sort_order, category_type)
SELECT nextval('school_inventory_categories_seq'), sch.id, 'Needs repair', null, 'STATUS'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM school_inventory_categories
    WHERE title='Needs repair'
    AND category_type='STATUS')
GROUP BY sch.id;


INSERT INTO school_inventory_categories (category_id, school_id, title, sort_order, category_type)
SELECT nextval('school_inventory_categories_seq'), sch.id, 'Buy more', null, 'STATUS'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM school_inventory_categories
    WHERE title='Buy more'
    AND category_type='STATUS')
GROUP BY sch.id;


INSERT INTO school_inventory_categories (category_id, school_id, title, sort_order, category_type)
SELECT nextval('school_inventory_categories_seq'), sch.id, 'Lended', null, 'STATUS'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM school_inventory_categories
    WHERE title='Lended'
    AND category_type='STATUS')
GROUP BY sch.id;
